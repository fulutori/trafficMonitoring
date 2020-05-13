#coding: utf-8
import datetime
import sqlite3
import os


traffic_dir = '/mnt/hdd/RoomManager/trafficData'

traffic_db = '/mnt/hdd/RoomManager/db/traffic.db'
traffic_conn = sqlite3.connect(traffic_db)
traffic_cursor = traffic_conn.cursor()



if __name__ == '__main__':
	# 7日前の日付を取得
	now = datetime.datetime.now()
	ago7day = int((now - datetime.timedelta(days=7)).strftime('%Y%m%d'))
	
	# pcapファイルの一覧を降順で取得
	files = sorted(os.listdir(traffic_dir))

	# 7日よりも前のファイルは削除
	for file in files:
		if int(file[:8]) < ago7day:
			os.remove('{}/{}'.format(traffic_dir, file))
		else:
			break
