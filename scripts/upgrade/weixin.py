#!/usr/bin/env python
# -*- coding: UTF-8 -*-
"""
升级MUFE的示例脚本
python weixin.py -s 52c4dd514a9619360d8b58af -t 548a61234a96197a638b4597 -w 52c4d890499619204a999846 -d http://131115-e0262.umaman.com
-s --source设定来源项目
-t --target设定目标项目
-w --weixin 参数设定UMA系统中的微信项目编号
-d --domain UMA系统下使用的微信域名
升级后不能关闭原有UMA下的微信站点，除非原有网站再无任何关联内容，可能存在关联的内容。

"""
from pymongo import MongoClient
from bson.objectid import ObjectId
from pymongo import ASCENDING, DESCENDING
from bson.code import Code
import gridfs

from email.mime.text import MIMEText
from datetime import datetime,date
import smtplib
import time
import json
import sys
import optparse
reload(sys)
sys.setdefaultencoding('UTF-8')

#client = MongoClient('127.0.0.1', 27017)
client = MongoClient('10.0.0.31', 57017)
icc = client.ICCv1
fs_icc = gridfs.GridFS(icc,'icc')

#client1 = MongoClient('127.0.0.1', 27017)
client1 = MongoClient('10.0.0.31', 27017)
uma = client1.umav3
fs_uma = gridfs.GridFS(uma)

import logging
logging.basicConfig(
    level=logging.DEBUG,
    format='%(asctime)s %(filename)s[line:%(lineno)d] %(levelname)s %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S'
)


def file_from_uma_to_icc(uma_file_id):
    try:
        uma_file_id = uma_file_id.split("/id/")[1]
        #logging.debug(uma_file_id)
        uma_file = fs_uma.get(ObjectId(uma_file_id))
        filename = 'fileName' in uma_file and uma_file.fileName or ''
        mime = 'fileMime' in uma_file and uma_file.fileMime or ''
        icc_file = fs_icc.put(uma_file.read(),filename=filename,mime=mime)
        #
        
        logging.debug(str(icc_file))
        return str(icc_file)
    except Exception,e:
        logging.exception(uma_file_id)
        logging.exception(str(e))
        return False

parser = optparse.OptionParser()
parser.add_option("-s", "--source", action="store", type="string",
                      dest="source_pid", default=None,
                      help="""请使用-s --source设定来源项目""")

parser.add_option("-t", "--target", action="store", type="string",
                      dest="target_pid", default=None,
                      help="""请使用-t --target设定目标项目""")

parser.add_option("-w", "--weixin", action="store", type="string",
                      dest="weixin_pid", default=None,
                      help="""请使用-w 或者--weixin 参数设定UMA系统中的微信项目编号""")

parser.add_option("-d", "--domain", action="store", type="string",
                      dest="domain", default=None,
                      help="""请使用-d 或者--domain UMA系统下使用的微信域名""")

(options, args) = parser.parse_args()


if options.source_pid is None:
    logging.debug('请设定-s 或者--source 参数,UMA系统中的项目编号')
    sys.exit(2)
    
if options.target_pid is None:
    logging.debug('请设定-t 或者--target 参数,ICC系统中的项目编号')
    sys.exit(2)
    
if options.weixin_pid is None:
    logging.debug('请设定-w 或者--weixin 参数,UMA系统中的微信项目编号')
    sys.exit(2)
    
if options.domain is None:
    logging.debug('请使用-d 或者--domain UMA系统下使用的微信域名')
    sys.exit(2)

#获取UMA中的集合编号
page_form_id = str(uma['iDatabase.form'].find_one({'projectId':options.source_pid,'formAlias':'iWeixin_page'})['_id'])
reply_form_id = str(uma['iDatabase.form'].find_one({'projectId':options.source_pid,'formAlias':'iWeixin_reply'})['_id'])
keyword_form_id = str(uma['iDatabase.form'].find_one({'projectId':options.source_pid,'formAlias':'iWeixin_keyword'})['_id'])
rsh_keyword_reply_form_id = str(uma['iDatabase.form'].find_one({'projectId':options.source_pid,'formAlias':'iWeixin_rsh_keyword_reply'})['_id'])
request_form_id = str(uma['iDatabase.form'].find_one({'projectId':options.source_pid,'formAlias':'iWeixin_request'})['_id'])


#获取ICC中的集合编号
page_collection_id = str(icc['idatabase_collections'].find_one({'project_id':options.target_pid,'alias':'iWeixin_page'})['_id'])
reply_collection_id = str(icc['idatabase_collections'].find_one({'project_id':options.target_pid,'alias':'iWeixin_reply'})['_id'])
keyword_collection_id = str(icc['idatabase_collections'].find_one({'project_id':options.target_pid,'alias':'iWeixin_keyword'})['_id'])
user_collection_id = str(icc['idatabase_collections'].find_one({'project_id':options.target_pid,'alias':'iWeixin_user'})['_id'])
source_collection_id = str(icc['idatabase_collections'].find_one({'project_id':options.target_pid,'alias':'iWeixin_source'})['_id'])
menu_collection_id = str(icc['idatabase_collections'].find_one({'project_id':options.target_pid,'alias':'iWeixin_menu'})['_id'])

#升级自定义页面
page_map = {}
logging.info('开始升级自定义页面')
logging.info('读取集合为：iDatabase.%s'%(page_form_id,))
logging.info('读取条数:%s'%(uma['iDatabase.%s'%(page_form_id,)].count(),))
icc['idatabase_collection_%s'%(page_collection_id,)].drop()
logging.info('写入集合为：idatabase_collection_%s drop完成'%(page_collection_id,))
for page_info in uma['iDatabase.%s'%(page_form_id,)].find():
    data = {}
    page_info_id = str(page_info['_id'])
    data['title'] = 'title' in page_info and page_info['title'] or ''
    data['picture'] = 'image' in page_info and page_info['image'] or ''
    data['content'] = 'content' in page_info and page_info['content'] or ''
    data['__REMOVED__'] = False
    data['__CREATE_TIME__'] = page_info['createTime']
    data['__MODIFY_TIME__'] = page_info['createTime']
    new_page_id = icc['idatabase_collection_%s'%(page_collection_id,)].insert(data)
    page_map[page_info_id] = str(new_page_id)
logging.info('写入集合为：idatabase_collection_%s 写入完成'%(page_collection_id,))
logging.info('完成升级自定义页面')

#升级回复内容
reply_map = {}
logging.info('开始升级回复内容')
logging.info('读取集合为：iDatabase.%s'%(reply_form_id,))
logging.info('读取条数:%s'%(uma['iDatabase.%s'%(reply_form_id,)].count(),))
icc['idatabase_collection_%s'%(reply_collection_id,)].drop()
logging.info('写入集合为：idatabase_collection_%s drop完成'%(reply_collection_id,))
for reply_info in uma['iDatabase.%s'%(reply_form_id,)].find():
    data = {}
    reply_info_id = str(reply_info['_id'])
    data['keyword'] = 'keyword' in reply_info and reply_info['keyword'] or ''
    data['reply_type'] = 'reply_type' in reply_info and reply_info['reply_type'] or ''
    data['title'] = 'title' in reply_info and reply_info['title'] or ''
    data['url'] = 'url' in reply_info and reply_info['url'] or ''
    data['description'] = 'description' in reply_info and reply_info['description'] or ''
    
    picture = 'picture' in reply_info and reply_info['picture'] or ''
    if picture!='':
        data['picture'] = file_from_uma_to_icc(picture)
    
    icon = 'icon' in reply_info and reply_info['icon'] or ''
    if icon!='':
        data['icon'] = file_from_uma_to_icc(icon)
    elif picture!='':
        data['icon'] = data['picture']       
    
    data['music'] = 'music' in reply_info and reply_info['music'] or ''
    data['priority'] = 'priority' in reply_info and reply_info['priority'] or ''
    data['page'] = 'page' in reply_info and reply_info['page'] or ''
    
    #if data['page']!='' and data['url']=='':
    #    #data['url'] = "http://131115-e0262.umaman.com/weixin/index2/page/type/custom/id/%s"%(data['page'],)
    #    data['url'] = "%s/weixin/index2/page/type/custom/id/%s"%(options.domain,data['page'])
        
    if data['page']!='':
        data['page'] = data['page'] in page_map and page_map[data['page']] or ''
         
    data['show_times'] = 'show_times' in reply_info and reply_info['show_times'] or ''
    data['click_times'] = 'click_times' in reply_info and reply_info['click_times'] or ''
    
    data['__REMOVED__'] = False
    data['__CREATE_TIME__'] = reply_info['createTime']
    data['__MODIFY_TIME__'] = reply_info['createTime']
    new_reply_id = icc['idatabase_collection_%s'%(reply_collection_id,)].insert(data)
    reply_map[reply_info_id] = str(new_reply_id)
logging.info('完成升级回复内容')

#升级关键词内容
keyword_map = {}
logging.info('开始关键词内容')
logging.info('读取集合为：iDatabase.%s'%(keyword_form_id,))
logging.info('读取条数:%s'%(uma['iDatabase.%s'%(keyword_form_id,)].count(),))
icc['idatabase_collection_%s'%(keyword_collection_id,)].drop()
logging.info('写入集合为：idatabase_collection_%s drop完成'%(keyword_collection_id,))
for keyword_info in uma['iDatabase.%s'%(keyword_form_id,)].find():
    keyword_info_id = str(keyword_info['_id'])
    rsh = []
    reply_type = 3
    for rsh_keyword_reply_info in uma['iDatabase.%s'%(rsh_keyword_reply_form_id,)].find({'keyword':keyword_info_id}):
        if reply_map.has_key(str(rsh_keyword_reply_info['reply'])):
            rsh.append(reply_map[str(rsh_keyword_reply_info['reply'])])
            reply_type = uma['iDatabase.%s'%(reply_form_id,)].find_one({'_id':ObjectId(rsh_keyword_reply_info['reply'])})['reply_type']
        else:
            logging.debug("key no in reply_map")
            
    logging.debug("rsh:%s"%(','.join(rsh,)))
    logging.debug("reply_type:%d"%(reply_type,))
    
    data = {}
    data['keyword'] = 'keyword' in keyword_info and keyword_info['keyword'] or ''
    logging.debug('match type is %s'%(keyword_info['match_type'],))
    if int(keyword_info['match_type'])==1:
        data['fuzzy'] = False
    else:
        data['fuzzy'] = True

    data['reply_type'] = reply_type
    data['reply_ids'] = rsh
    data['priority'] = 0
    data['times'] = 0
    data['__REMOVED__'] = False
    data['__CREATE_TIME__'] = keyword_info['createTime']
    data['__MODIFY_TIME__'] = keyword_info['createTime']
    new_keyword_id = icc['idatabase_collection_%s'%(keyword_collection_id,)].insert(data)
    keyword_map[keyword_info_id] = str(new_keyword_id)
logging.info('完成升级关键词内容')

#升级自定义菜单
logging.info('开始升级微信自定义菜单')
icc['idatabase_collection_%s'%(menu_collection_id,)].drop()
parent_map = {}
for menu_info in uma['weixin.menu'].find({'project.projectId':options.weixin_pid}):
    data = {}
    data['type'] = 'type' in menu_info and menu_info['type'] or ''
    data['name'] = 'name' in menu_info and menu_info['name'] or ''
    data['key'] = 'key' in menu_info and menu_info['key'] or ''
    data['parent'] = 'fatherNode' in menu_info and menu_info['fatherNode'] or ''
    data['priority'] = 'showOrder' in menu_info and menu_info['showOrder'] or 0
    data['__REMOVED__'] = False
    data['__CREATE_TIME__'] = menu_info['createTime']
    data['__MODIFY_TIME__'] = menu_info['createTime']
    new_menu_id = icc['idatabase_collection_%s'%(menu_collection_id,)].insert(data)
    parent_map[str(menu_info['_id'])] = str(new_menu_id)
logging.info('更新父ID信息')    
for has_father_menu_info in icc['idatabase_collection_%s'%(menu_collection_id,)].find({'fatherNode':{'$ne':''}}):
    icc['idatabase_collection_%s'%(menu_collection_id,)].update({'_id':has_father_menu_info['_id']},{'$set':{'parent':parent_map[has_father_menu_info['parent']]}})
logging.info('完成升级自定义菜单')

"""
#升级微信用户数据
logging.info('开始升级微信用户数据')
logging.info('读取集合为：weixin.user')
user_number = uma['weixin.user'].count()
logging.info('读取条数:%d'%(user_number,))
icc['idatabase_collection_%s'%(user_collection_id,)].drop()
logging.info('写入集合为：idatabase_collection_%s drop完成'%(user_collection_id,))
print {'project.projectId':options.source_pid}
#for user_info in uma['weixin.user'].find({'project.projectId':'52c4d890499619204a999846'}):
for user_info in uma['weixin.user'].find({'project.projectId':options.weixin_pid}):
    if user_info.has_key('weixin'):
        data = user_info['weixin']
        if data.has_key('subscribe'):
            if data['subscribe']==1:
                data['subscribe'] = True
            else:
                data['subscribe'] = False
        data['__REMOVED__'] = False
        data['__CREATE_TIME__'] = user_info['createTime']
        data['__MODIFY_TIME__'] = user_info['createTime']
        icc['idatabase_collection_%s'%(user_collection_id,)].insert(data)

##升级回复原始记录
logging.info('开始升级微信用户原始记录')
icc['idatabase_collection_%s'%(source_collection_id,)].drop()
#for record_info in uma['weixin'].find({'project_id':'52c4d890499619204a999846'}):
for record_info in uma['weixin'].find({'project_id':options.weixin_pid}):
    del record_info['_id']
    record_info['__REMOVED__'] = False
    record_info['__CREATE_TIME__'] = record_info['createTime']
    record_info['__MODIFY_TIME__'] = record_info['createTime']
    icc['idatabase_collection_%s'%(source_collection_id,)].insert(record_info)

"""


    






