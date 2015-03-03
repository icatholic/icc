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


client = MongoClient('10.0.0.31', 57017)
db = client.ICCv1
mr = client.mapreduce
collection = db['idatabase_collection_53ba403948961943778b45b4']
collection = db['idatabase_collection_53ba403948961943778b45b4']
m = """
function() {
    emit(this.city,this);
}
"""

r = """
function(key,values) {
    
}
"""

out = SON([('replace', 'lianhelihua'), ('db', 'mapreduce')])
collection.map_reduce(m,r,out)


    
