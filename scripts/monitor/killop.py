#/usr/bin/python
# -*- coding: UTF-8 -*- 
from pymongo import MongoClient
from bson.objectid import ObjectId
from pymongo import ASCENDING, DESCENDING
from bson.code import Code
from email.mime.text import MIMEText
from datetime import datetime,date
import smtplib
import time
import json
import sys
reload(sys)
sys.setdefaultencoding('UTF-8')

mongos = ['10.0.0.30','10.0.0.31','10.0.0.32']
exclude = ['local.oplog.rs','ICCv1.','local.','ICCv1.system.indexes','ICCv1.icc.chunks','mapreduce.system.indexes','']
for server in mongos:
    client = MongoClient(server, 57017)
    db = client.ICCv1
    ops = db.current_op()
    
    killop = []
    for op in ops[u'inprog']:
        cmd = "db.killOp(%s)"%(op[u'opid'],)
