#from my_http_server import MyHTTPRequestHandler, run
import my_http_server
import re
class HTTPRequestHandler(my_http_server.MyHTTPRequestHandler):
    def register_routes(self):
        d = {
            'category_id':r'\d+',
            'firstname_like':r'[A-Za-z]+',
            'lastname_like':r'[A-Za-z]+',
            'email_like':r'[-A-Za-z.@_]+',
            'gender_id':r'\d+',
            'limit':r'\d+',
            'offset':r'\d+',
            'age':r'\d+',
            'bday':r'\d+',
            'bmonth':r'\d+',
            'byear':r'\d+',
            'min_age':r'\d+',
            'max_age':r'\d+',
            }
        d_str = r'|'.join('(?:%s\=%s)'%(re.escape(k),v)
                          for k,v in d.items())
        self.register_route(r'^/api/data/\?((?:(?:%s)\&?)*)/?$'%d_str,self.get_data)
        self.register_route(r'^/api/dictionaries/?$',self.get_dictionaries)

    def get_data(self,match):
        global sql_select_all
        d=dict(
            tuple(i.split('='))
            for i in match.group(1).split('&')
            )
        cursor = my_http_server.sql_connection.cursor(buffered=True)
        sql = [sql_select_all]
        params=[]
        where_dict = {
            'category_id':('category_id = %s',lambda x:[int(x)]),
            'gender_id':('gender_id = %s',lambda x:[int(x)]),
            'age':(
r'''(birthDate > date_sub(curdate(),interval %s year)) and
(birthDate <= date_sub(curdate(),interval %s year))''',
                  lambda x:[int(x)+1,int(x)]
                  ),
            'min_age':(
                'birthDate <= date_sub(curdate(),interval %s year)',
                lambda x:[int(x)]
                ),
            'max_age':(
                'birthDate > date_sub(curdate(),interval %s year)',
                lambda x:[int(x)+1]
                ),
            'bday':(
                'day(birthDate) = %s',
                lambda x:[int(x)]
                ),
            'bmonth':(
                'month(birthDate) = %s',
                lambda x:[int(x)]
                ),
            'byear':(
                'year(birthDate) = %s',
                lambda x:[int(x)]
                ),
            }
        where = [(v,where_dict[k])
                 for k,v in d.items() if k in where_dict]
        where_str = [] if not where else ['where %s'%(
            ' and '.join(s for v,(s,f) in where)
            )]
        sql.extend(where_str)
        params.extend([i for v,(s,f) in where for i in f(v)])
        if any(i in d for i in ['age','max_age','min_age']):
            sql.append('order by birthDate asc')
        if all(i in d for i in ['limit','offset']):
            sql.append('limit %s offset %s')
            params.extend([int(d['limit']),int(d['offset'])])
        print('\n'.join(sql[1:]),tuple(params),sep='\n')
        cursor.execute(' '.join(sql),tuple(params))
        res = list(cursor)
        cursor.close()
        return self.send_response_headers_json(res,gzip=True)

    def get_dictionaries(self,match):
        d = {
            'gender':'select id,name from test.gender',
            'category':'select id,name from test.category',
            'age':'select distinct cast(%s as signed) from test.client'%(
                sql_select_age('birthDate')
                )
            }
        for i in 'year,month,day'.split(','):
            d['b%s'%i]='select distinct %s(birthDate) from test.client'%i
        cursor = my_http_server.sql_connection.cursor(buffered=True)
        res = {}
        for key,sql in d.items():
            cursor.execute(sql)
            res[key]=dict(sorted(i if len(i)==2 else i*2 for i in cursor))
        cursor.close()
        return self.send_response_headers_json(res,gzip=True)

#https://stackoverflow.com/a/2533913
sql_select_age = lambda date_field:(
r'''DATE_FORMAT(NOW(), '%Y') -
DATE_FORMAT('''+date_field+''', '%Y') -
(DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT('''+date_field+''', '00-%m-%d'))'''
    )
sql_select_all = r'''
SELECT  
client.id as id, 
category.name as category, 
firstname, 
lastname, 
email, 
gender.name as gender,  
cast(birthDate as char) as birthDate,
%s as age
FROM test.client
join test.gender on gender.id=client.gender_id
join test.category on category.id=client.category_id
'''%sql_select_age('birthDate')

my_http_server.run(HTTPRequestHandler,{
    'user':'root',
    'password':'12345678',
    'database':'test',
    'port':33061,
    })
