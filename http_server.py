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
        self.register_route(r'^/api/(product|review)/store',
                            self.store_product_or_review)
        
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
        product_data = {i['id']:i
            for i in data['product']
            }
        for k in product_data:
            product_data[k]['reviews']=[]
        for n,i in enumerate(data['review']):
            data['review'][n]['timestamp'] = (
                calendar.timegm(
                time.strptime(
                data['review'][n]['time_str'],
                '%Y-%m-%dT%H:%M:%S%z'
                )
                )
                )
        for i in data['review']:
            product_data[i['product_id']]['reviews'].append(i)
        cursor.close()
        return self.send_response_headers_json(
            list(product_data.values()),gzip=True)

    def store_product_or_review(self,match):
        global sql_connection,store_action_sql
        if not (self.headers and
            (s:=[self.headers[i] for i in self.headers
                 if i.lower() == 'Content-Length'.lower()])):
            return self.no_content(match)
        s=self.rfile.read(int(s[0]))
        
        cursor = sql_connection.cursor(buffered=True)
        cursor.execute(
            store_action_sql({'like':'liking','done':'done'}[match.group(1)]),
            match.groups()[1:]+(match.group(3),))
        sql_connection.commit()
        cursor.close()
        #print(match.groups())
        return self.no_content(match)


Handler = HTTPRequestHandler

sql_connection=(obtain_sql_connection:=lambda:(
    mysql.connector.connect(
        user='root',
        password='12345678',
        database='test',
        port=33061
        )
))()

fields = {'review':(
    'review join `user` on `user`.id = review.user_id',
    {'user_name':'`user`.name',
     'rating':'review.rating * 2',
     'comment':'review.comment',
     'time_str':'review.`time`',
     'product_id':'review.product_id',
     'n':'n'}
    ),
          'product':(
    'product join merchant on product.merchant_id = merchant.id',
    {'name':'product.name',
     'picture':'product.contest_page_picture',
     'value':'product.value',
     '`timestamp`':'product.generation_time',
     'merchant_name':'merchant.title',
     'id':'product.id'}
    )}

sql_insert_vacancy = lambda action:(
'INSERT INTO vacancy (id,%s) VALUES (%%s,%%s) '
'ON DUPLICATE KEY UPDATE %s=%%s'
)%((action,)*2)

store_action_sql = lambda action:(
    'INSERT INTO actions (vacancy_id,%s) VALUES (%%s,%%s) '
    'ON DUPLICATE KEY UPDATE %s=%%s'
    )%((action,)*2)

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
