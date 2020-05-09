#coding: utf-8
import sqlite3
import os
import re

dbname = '/mnt/hdd/RoomManager/db/device.db'

conn = sqlite3.connect(dbname)
cursor = conn.cursor()


def make_tabel():
	create_table = 'CREATE TABLE devices (device varchar(64), mac varchar(17), ip varchar(15))'
	cursor.execute(create_table)


def insert_device():
	print('-------------------------------------')
	print('端末を登録します\n')
	device = check_device()
	if device == -1:
		print('\n登録がキャンセルされました')
		return

	mac = check_mac()
	if mac == -1:
		print('\n登録がキャンセルされました')
		return

	ip = check_ip()
	if ip == -1:
		print('\n登録がキャンセルされました')
		return

	data = [device, mac, ip]
	
	if cursor.execute('INSERT INTO devices (device, mac, ip) VALUES (?, ?, ?)', data):
		conn.commit()
		cursor.execute('SELECT * FROM devices WHERE mac=?', [mac])
		in_device = cursor.fetchall()[0]
		print('-------------------------------------')
		print('以下の内容で登録しました')
		print('登録名\t\t：{}\nMACアドレス\t：{}\nIPアドレス\t：{}'.format(in_device[0], in_device[1], in_device[2]))


def check_device():
	while True:
		device = input('登録名\n>> ')

		# 「n」のときはキャンセル
		if device == 'n':
			return -1

		cursor.execute('SELECT * FROM devices WHERE device=?', [device])
		if len(cursor.fetchall()) == 0:
			return device
		else:
			print('\n「{}」は既に登録されています\n別の名前で登録してください\n※キャンセルする場合は「n」を入力\n'.format(device))



def check_mac():
	while True:
		mac = input('MACアドレス\n>> ')
		
		# 「n」のときはキャンセル
		if mac == 'n':
			return -1

		# MACアドレスが正しいかチェック
		if re.match(r"..:..:..:..:..:..$", mac):
			return mac
		else:
			print('\nMACアドレスの形式が不正です\nもう一度入力してください\n※キャンセルする場合は「n」を入力\n')


def check_ip():
	while True:
		ip = input('IPアドレス\n>> ')

		# 「n」のときはキャンセル
		if ip == 'n':
			return -1

		# IPアドレスが正しいかチェック
		if re.match(r"[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$", ip):
			return ip
		else:
			print('\nIPアドレスの形式が不正です\nもう一度入力してください\n※キャンセルする場合は「n」を入力\n')



def get_device():
	print('-------------------------------------')
	print('登録済み端末一覧\n')
	cursor.execute('SELECT * FROM devices')
	for device in cursor.fetchall():
		print('登録名：{}\tMACアドレス：{}\tIPアドレス：{}'.format(device[0], device[1], device[2]))


def delete_device():
	print('-------------------------------------')
	print('端末を削除します\n')
	get_device()

	while True:
		device = input('\n削除する端末の登録名を入力してください\n※キャンセルする場合は「n」を入力\n>> ')
		if device == 'n':
			print('端末削除をキャンセルしました')
			break

		cursor.execute('SELECT device FROM devices WHERE device=?', [device])
		if not len(cursor.fetchall()) == 0:
			if cursor.execute('DELETE FROM devices WHERE device=?', [device]):
				conn.commit()
				print('「{}」を削除しました\n'.format(device))
				break
		else:
			print('「{}」はデータベースに存在しません\n'.format(device))


def update_device():
	print('-------------------------------------')
	print('端末を更新します\n')
	get_device()

	while True:
		device = input('\n更新する端末の登録名を入力してください\n※キャンセルする場合は「n」を入力\n>> ')
		if device == 'n':
			print('端末更新をキャンセルしました')
			break

		cursor.execute('SELECT device FROM devices WHERE device=?', [device])
		if not len(cursor.fetchall()) == 0:
			mac = check_mac()
			if mac == -1:
				print('\n更新がキャンセルされました')
				return

			ip = check_ip()
			if ip == -1:
				print('\n更新がキャンセルされました')
				return

			if cursor.execute('UPDATE devices SET mac=?, ip=? WHERE device=?', [mac, ip, device]):
				conn.commit()
				print('\n「{}」を更新しました'.format(device))
				print('MACアドレス\t：{}\nIPアドレス\t：{}'.format(mac, ip))
				break
		else:
			print('\n「{}」はデータベースに存在しません\n'.format(device))


if __name__ == '__main__':
	# テーブルが存在しないときは作成する
	check_table = "SELECT * FROM sqlite_master WHERE type='table'"
	cursor.execute(check_table)
	if len(cursor.fetchall()) == 0:
		make_tabel()
		print('テーブルを作成しました')
		print('-------------------------------------')
	

	while True:
		try:
			ans = int(input('登録済み端末の表示\t：1\n端末登録\t\t：2\n端末削除\t\t：3\n端末更新\t\t：4\n何もしない\t\t：5\n>> '))
		except:
			ans = -1
		
		# 登録済みの端末を表示
		if ans == 1:
			get_device()

		# 登録
		elif ans == 2:
			insert_device()

		# 削除
		elif ans == 3:
			delete_device()

		# 更新
		elif ans == 4:
			update_device()

		else:
			break

		print('\n-------------------------------------\n')


	# データベースを切断
	conn.close()


