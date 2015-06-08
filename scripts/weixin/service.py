#/usr/bin/python
# -*- coding: UTF-8 -*- 
import tornado.httpserver
import tornado.ioloop
import tornado.options
import tornado.web
import tornado.httpclient

import urllib
import json
import datetime
import time
import re
import urlparse
import hashlib
import time
import json

from pymongo import MongoClient
from bson.objectid import ObjectId
from pymongo import ASCENDING, DESCENDING
from bson.code import Code


from tornado.options import define, options
define("port", default=8000, help="run on the given port", type=int)

client = MongoClient('10.0.0.31', 57017)
db = client.ICCv1

class ComplexEncoder(json.JSONEncoder):
    def default(self, obj):
        if isinstance(obj, datetime):
            return obj.strftime('%Y-%m-%d %H:%M:%S')
        elif isinstance(obj, date):
            return obj.strftime('%Y-%m-%d')
        elif isinstance(obj, object):
            return str(obj)
        else:
            return json.JSONEncoder.default(self, obj)

def get_project_id_by_host(http_host):
    rst = db.iWeixin_rsh_service_project.find_one({'serive_http_host':{'$regex':http_host,'$options':'i'},'__REMOVED__':False})
    print rst
    if rst == None:
        print "Please set http_host in icc dashboard."
        return None
    else:
        return unicode(rst[u'project_id'])

class Project():
    def __init__(self,project_id):
        self.project_id = project_id
    
    def get_weixin_config(self):
        print "get_weixin_config query"
        print {'project_id':self.project_id,'alias':'iWeixin_application','__REMOVED__':False}
        weixin_application = db.idatabase_collections.find_one({'project_id':self.project_id,'alias':'iWeixin_application','__REMOVED__':False})
        print "weixin_application"
        print weixin_application
        collection_name = "idatabase_collection_%s"%(str(weixin_application[u'_id']),)
        print "collection_name is"
        print collection_name
        return db[collection_name].find_one({'is_product':True,'__REMOVED__':False})
    
    def update_user_info(self,datas):
        weixin_application = db.idatabase_collections.find_one({'project_id':self.project_id,'alias':'iWeixin_user','__REMOVED__':False})
        collection_name = "idatabase_collection_%s"%(str(weixin_application[u'_id']),)
        collection = db[collection_name]
        if datas.has_key('subscribe'):
            datas['subscribe'] = datas['subscribe'] and True or False
        user_info = collection.find_one({'openid':datas['openid'],'__REMOVED__':False})
        
        print user_info
        print datas
        if user_info==None:
            datas['__REMOVED__'] = False;
            now = datetime.datetime.utcnow()
            datas['__CREATE_TIME__'] = now
            datas['__MODIFY_TIME__'] = now
            print collection.insert(datas)
        else:
            print collection.update({'_id':user_info['_id']},{'$set':datas})
        return True


class AuthorizeHandler(tornado.web.RequestHandler):
    def get(self):
        try:
            project_id = self.get_argument('project_id',None)
            if project_id==None:
                project_id = get_project_id_by_host(self.request.host)
            p = Project(project_id)
            weixin_config = p.get_weixin_config()
            print weixin_config
            redirect = self.get_argument('redirect',None)
            scope = self.get_argument('scope','snsapi_userinfo')#snsapi_base|snsapi_userinfo
            
            if redirect==None:
                return self.write("""Please set the redirect parameter.""")
            
            append = self.get_secure_cookie('__WEIXIN_OAUTH_INFO__')
            append = None
            if append!=None:
                redirect = urllib.unquote(redirect)
                url = unicode("%s%s%s"%(redirect,redirect.find('?')>0 and '&' or '?',append)).encode('utf-8')
                self.redirect(url)
            else:    
                redirect_uri = self.redirect_uri(redirect)
                url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=%s&state=%s#wechat_redirect"%(weixin_config[u'appid'], redirect_uri,scope,scope)
                self.redirect(url)
        except Exception,e:
            return self.write(str(e))
    
    def redirect_uri(self, redirect):
        redirect = unicode(redirect).encode('utf-8')
        if redirect.find("%")==-1:
            redirect = urllib.quote(redirect)
        return urllib.quote("http://%s/weixin/sns/callback?redirect=%s"%(self.request.host,redirect)) 
        
class AccessTokenHandler(tornado.web.RequestHandler): 
    def sign(self, openid, secret_key, timestamp):
        return hashlib.sha1("%s|%s|%s"%(openid, secret_key, timestamp)).hexdigest()
    
    @tornado.web.asynchronous
    def get(self):
        self.client = tornado.httpclient.AsyncHTTPClient()
        self.redirect = ''
        self.access_token = {}
        self.scope = ''
        self.secret_key = ''
        
        project_id = self.get_argument('project_id',None)
        if project_id==None:
            project_id = get_project_id_by_host(self.request.host)
        code = self.get_argument('code',None)
        state = self.get_argument('state')
        self.scope = state
        self.redirect = urllib.unquote(self.get_argument('redirect'))
        
        if code==None:
            return self.write("""User cancel the authorization.""")
        
        p = Project(project_id)
        weixin_config = p.get_weixin_config()
        appid = weixin_config[u'appid']
        secret = weixin_config[u'secret']
        self.secret_key = weixin_config[u'secretKey']
        print "weixin_config"
        print weixin_config
        self.client.fetch("https://api.weixin.qq.com/sns/oauth2/access_token?appid=%s&secret=%s&code=%s&scope=%s&grant_type=authorization_code"%(appid,secret,code,self.scope),callback=self.on_authorize,validate_cert=False)
    
    @tornado.web.asynchronous
    def on_authorize(self, response):
        body = json.loads(response.body)
        
        if body.has_key('errmsg') and body['errmsg']!='ok':
            self.write("on_authorize error:%s"%(response.body,))
            self.finish() 
        else:
            self.access_token = body
            access_token = unicode(body[u'access_token'])
            expires_in = unicode(body[u'expires_in'])
            refresh_token = unicode(body[u'refresh_token'])
            openid = unicode(body[u'openid'])
            scope = unicode(body[u'scope'])

            print "scope"
            print scope
            print self.scope
            if scope=='snsapi_base':
                project_id = self.get_argument('project_id',None)
                if project_id==None:
                    project_id = get_project_id_by_host(self.request.host)
                p = Project(project_id)
                datas = {}
                datas['openid'] = openid
                datas['access_token'] = body
                p.update_user_info(datas)
                self.redirect_append_params({})
            else:
                self.client.fetch("https://api.weixin.qq.com/sns/userinfo?access_token=%s&openid=%s&lang=zh_CN"%(access_token,openid),callback=self.on_getuserinfo,validate_cert=False)
    
    def on_getuserinfo(self,response):
        user_info = json.loads(response.body)
        if user_info.has_key('errmsg') and user_info['errmsg']!='ok':
            self.write("on_getuserinfo error:%s"%(response.body,))
            self.finish() 
        else:
            #写入数据库
            project_id = self.get_argument('project_id',None)
            if project_id==None:
                project_id = get_project_id_by_host(self.request.host)
            p = Project(project_id)
            user_info['access_token'] = self.access_token
            p.update_user_info(user_info)
            self.redirect_append_params(user_info)
            
         
    
    def redirect_append_params(self,datas={}):
        try:
            append = ''
            if self.access_token.has_key('openid'):
                timestamp = int(time.time())
                sign = self.sign(self.access_token['openid'],self.secret_key,timestamp)
                base = {"FromUserName": self.access_token['openid'],"scope":self.access_token['scope'],'timestamp':timestamp,'signkey':sign}
                params = dict(base, **datas)
                new_params = {};
                for k,v in params.items():
                    if isinstance(v, basestring):
                        new_params[k] = v.encode('utf-8')
                    elif isinstance(v,int):
                        new_params[k] = v
                
                try:       
                    if 'openid' in new_params:
                        del new_params['openid']
                    if 'province' in new_params:
                        del new_params['province']
                    if 'city' in new_params:
                        del new_params['city']
                    if 'language' in new_params:
                        del new_params['language']
                except Exception,e:
                    pass
                
                append = urllib.urlencode(new_params)
                self.set_secure_cookie('__WEIXIN_OAUTH_INFO__',append,30)
            else:
                self.write("""self.access_token is undefined""")
                self.finish()
            
            url = unicode("%s%s%s"%(self.redirect,self.redirect.find('?')>0 and '&' or '?',append)).encode('utf-8')
            self.write("""<html><head><meta http-equiv="refresh" content="0; url=%s" /></head><body></body></html>"""%(url,))
            self.finish()
        except Exception,e:
            print e
        

if __name__ == "__main__":
    tornado.options.parse_command_line()
    app = tornado.web.Application([
        (r"/weixin/sns/index", AuthorizeHandler),
        (r"/weixin/sns/callback", AccessTokenHandler)
    ],autoreload=True,cookie_secret="72DDE445B09542BF0BC2F3E2E172EE6B")
    http_server = tornado.httpserver.HTTPServer(app)
    http_server.listen(options.port)
    tornado.ioloop.IOLoop.instance().start()


