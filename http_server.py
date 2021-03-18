import http.server
import socketserver
from http import HTTPStatus
import sys
import io
import re
import json
import pickle
import mysql.connector
import os
import os.path
import gzip
import time
import calendar
from PIL import Image
import io
import urllib.request
import base64
import urllib.parse

PORT = 8000

Handler = http.server.SimpleHTTPRequestHandler

class HTTPRequestHandler(http.server.SimpleHTTPRequestHandler):
    def __init__(self, *args, **kwargs):
        #global sql_connection
        self.routes = []
        self.register_routes()
        self.do_POST = self.do_GET
        #self.cnx = sql_connection
        super().__init__(*args, **kwargs)

    def do_GET(self):
        """Serve a GET request."""
        #if self.path.startswith('/api/'):
        #    f = self.send_response_headers('api call')
        #else:
        f=self.route()
        if f==False:
            f = self.send_head()
        if f:
            try:
                self.copyfile(f, self.wfile)
            finally:
                f.close()

    def send_response_headers(self,data,
                              content_type=None,
                              status=None,
                              #headers={},
                              gzip_output=False):
        if not isinstance(data,list):
            r=[data]
        else:
            r=data
        enc = sys.getfilesystemencoding()
        encoded = '\n'.join(r).encode(enc, 'surrogateescape')
        if gzip_output:
            encoded=gzip.compress(encoded)
            headers={'Content-Encoding':'gzip'}
        else:
            headers={}
        headers['Access-Control-Allow-Origin']='*'#
        f = io.BytesIO()
        f.write(encoded)
        f.seek(0)
        self.send_response(status or HTTPStatus.OK)
        for k,v in headers.items():
            self.send_header(k,v)
        self.send_header("Content-type",
                         ("%s; charset=%s" %
                          (content_type or "text/html", enc)))
        self.send_header("Content-Length", str(len(encoded)))
        self.end_headers()
        return f

    def register_route(self,route,handler):
        self.routes.append((re.compile(route),handler))

    def route(self):
        global sql_connection
        for matcher,handler in self.routes:
            match=matcher.match(self.path)
            if match:
                try:
                    return handler(match)
                except mysql.connector.errors.OperationalError:
                    sql_connection=obtain_sql_connection()
                    return handler(match)
        return False

    def register_routes(self):
        self.register_route(r'^/api/data$',self.get_all_data)
        self.register_route(r'^/api/(product|review)/store$',
                            self.store_product_or_review)
        self.register_route(r'^/api/picture/check$',
                            self.check_picture_post)
        self.register_route(r'^/api/picture/(.*)$',
                            self.thumbnail)
        
    def send_response_headers_json(self,data,status=None,gzip=False):
        data_json=json.dumps(data)
        #application/json; charset=UTF-8
        return self.send_response_headers(
            data_json,'application/json',status,gzip_output=gzip
            )

    def no_content(self,match):
        #print(self.rfile.read())
        self.send_response(HTTPStatus.NO_CONTENT)
        self.end_headers()
        return

    def redirect(self,match):
        #data = match.group(1)
        self.send_response(HTTPStatus.MOVED_PERMANENTLY)
        self.send_header("Location","https://example.com/")
        self.end_headers()
        return

    def get_all_data(self,match):
        global sql_connection,fields
        cursor = sql_connection.cursor(buffered=True)
        data = dict()
        for table,(table_select,fields_dict) in fields.items():
            cursor.execute(
                ('select %s from %s')%(
                    ', '.join('%s as %s'%(v,k) for k,v in fields_dict.items()),
                    table_select
                    )
                )
            data[table] = [
                {k.strip("`"):v for k,v in zip(fields_dict,i)}
                for i in cursor
                ]
        #with open('data.pickle','wb') as fp:
        #    pickle.dump(data,fp)
        product_data = {i['id']:i
            for i in data['product']
            }
        for k,v in product_data.items():
            if v['image']:
                product_data[k]['image'] = base64.b64encode(v['image']).decode()
        for k in product_data:
            product_data[k]['reviews']=[]
##        for n,i in enumerate(data['review']):
##            data['review'][n]['timestamp'] = (
##                calendar.timegm(
##                time.strptime(
##                data['review'][n]['time_str'],
##                '%Y-%m-%dT%H:%M:%S%z'
##                )
##                )
##                )
        for i in data['review']:
            product_data[i['product_id']]['reviews'].append(i)
        cursor.close()
        return self.send_response_headers_json(
            list(product_data.values()),gzip=True)

    def store_product_or_review(self,match):
        global sql_connection,store_sql
        if not (self.headers and
            (s:=[self.headers[i] for i in self.headers
                 if i.lower() == 'Content-Length'.lower()])):
            return self.no_content(match)
        s=self.rfile.read(int(s[0]))
        data = json.loads(s)
        table = match.group(1)
        
        cursor = sql_connection.cursor(buffered=True)
        if table == 'review':
            cursor.execute(
                ('select max(n) from review_new '
                 'where product_id = %s',data['product_id']
                 )
                 )
            n=list(cursor)[0][0]
            data['n']=n+1 if n!=None else 0
        res={'success':True}
        if table == 'product':
            pic_png_data = self.check_picture(data['picture'])
            if isinstance(pic_png_data,Exception):
                cursor.close()
                return self.send_response_headers_json({
                    'success':False,
                    'exception':str(pic_png_data)
                    })
            else:
                data['image'] = pic_png_data
                res['image'] = base64.b64encode(pic_png_data).decode()
        cursor.execute(
            #store_action_sql({'like':'liking','done':'done'}[match.group(1)]),
            #match.groups()[1:]+(match.group(3),)
            store_sql(table),
            extract_fields_tuple(table,data)
            )
        #_id = list(cursor)[0][0];
        sql_connection.commit()
        _id = cursor.lastrowid #https://dev.mysql.com/doc/connector-python/en/connector-python-api-mysqlcursor-lastrowid.html
        res['id'] = _id
        cursor.close()
        #print(match.groups())
        #return self.no_content(match)
        return self.send_response_headers_json(res)

    def check_picture(self,url):
        try:
            req = urllib.request.Request(url, headers = {
                'User-Agent':
	'Mozilla/5.0 (X11; Linux x86_64; rv:87.0) Gecko/20100101 Firefox/87.0'
                })
            # https://stackoverflow.com/a/48249298
            with urllib.request.urlopen(req) as fp:
                data = fp.read()
            img = Image.open(io.BytesIO(data))
            # https://stackoverflow.com/a/273962
            img.thumbnail((40,40), Image.ANTIALIAS)
            # https://stackoverflow.com/a/646297
            with io.BytesIO() as output:
                img.save(output, format="PNG")
                contents = output.getvalue()
            return contents
        except Exception as e:
            return e

    def check_picture_post(self,match):
        if not (self.headers and
            (s:=[self.headers[i] for i in self.headers
                 if i.lower() == 'Content-Length'.lower()])):
            return self.send_response_headers_json({'success':False})
        s = self.rfile.read(int(s[0]))
        if not s:
            return self.send_response_headers_json({'success':False})
        data = json.loads(s)
        pic_png_data = self.check_picture(data)
        if isinstance(pic_png_data,Exception):
            return self.send_response_headers_json({
                'success':False,
                'exception':str(pic_png_data)
                })
        return self.send_response_headers_json({
            'success':True,
            'image':base64.b64encode(pic_png_data).decode()
            })

    def thumbnail(self,match):
        global sql_connection
        url = urllib.parse.unquote(match.group(1))
        pic_png_data = self.check_picture(url)
        if not isinstance(pic_png_data,Exception):
            cursor = sql_connection.cursor(buffered=True)
            cursor.execute(
                ('update product_new set image = %s '
                 'where picture = %s'),
                (pic_png_data,url)
                )
            sql_connection.commit()
            cursor.close()
            
            encoded = pic_png_data
            headers = dict()
            headers['Access-Control-Allow-Origin']='*'#
            f = io.BytesIO()
            f.write(encoded)
            f.seek(0)
            self.send_response(HTTPStatus.OK)
            for k,v in headers.items():
                self.send_header(k,v)
            self.send_header("Content-type", "image/png")
            self.send_header("Content-Length", str(len(encoded)))
            self.end_headers()
            return f
        else:
            return self.send_response_headers_json(str(pic_png_data))

Handler = HTTPRequestHandler

sql_connection=(obtain_sql_connection:=lambda:(
    mysql.connector.connect(
        user='root',
        password='12345678',
        database='test',
        port=33061,
        use_pure=True # https://stackoverflow.com/a/53468522 https://stackoverflow.com/a/55150960
        )
))()

##fields = {'review':(
##    'review join `user` on `user`.id = review.user_id',
##    {'user_name':'`user`.name',
##     'rating':'review.rating * 2',
##     'comment':'review.comment',
##     'time_str':'review.`time`',
##     'product_id':'review.product_id',
##     'n':'n'}
##    ),
##          'product':(
##    'product join merchant on product.merchant_id = merchant.id',
##    {'name':'product.name',
##     'picture':'product.contest_page_picture',
##     'value':'product.value',
##     '`timestamp`':'product.generation_time',
##     'merchant_name':'merchant.title',
##     'id':'product.id'}
##    )}
##
fields = {'review':(
    'review_new',
    {'user_name':'user_name',
     'rating':'rating',
     'comment':'comment',
     '`timestamp`':'unix_timestamp(created)',
     'product_id':'product_id',
     'n':'n'}
    ),
          'product':(
    'product_new',
    {'name':'name',
     'picture':'picture',
     'value':'value',
     '`timestamp`':'unix_timestamp(created)',
     'merchant_name':'merchant_name',
     'id':'id',
     '`image`':'`image`'}
    )}

fields_insert_list = {
    'review':('review_new',[
        'user_name',
        'rating',
        'comment',
        'product_id',
        'n',
        ]),
    'product':('product_new',[
        'name',
        'picture',
        'value',
        'merchant_name',
        'image'
        ])
    }

store_sql = lambda keyword:(
    'INSERT INTO %s (%s) VALUES (%s); '
    #'select LAST_INSERT_ID();'
    )%(
        fields_insert_list[keyword][0],
        ', '.join('`%s`'%i for i in fields_insert_list[keyword][1]),
        ', '.join(['%s']*len(fields_insert_list[keyword][1]))
        )

#store_fields = lambda keyword:fields_insert_list[keyword][1]
extract_fields_tuple = lambda keyword,data:tuple(
    data[i] for i in fields_insert_list[keyword][1]
    )

while PORT<8010:
    try:
        with socketserver.ThreadingTCPServer(("", PORT), Handler) as httpd:
            print("serving at port", PORT)
            httpd.serve_forever()
    except OSError:
        PORT+=1
    except KeyboardInterrupt:
        if sql_connection:
            sql_connection.close()
        raise
