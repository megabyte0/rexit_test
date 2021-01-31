import urllib.request
import re
from functools import reduce
urls=['https://www.ssa.gov/oact/babynames/decades/century.html',
'https://www.thoughtco.com/most-common-us-surnames-1422656',
      ]
s=(r'''<tr align="right"><td>1</td>
  <td >James</td> <td>4,735,694</td> <td >Mary</td> <td>3,265,105</td></tr>'''
   .replace('\n',' ')
   )
s_last_names=r'''<tr>
<td><p>1</p></td>
<td><p><a href="https://www.thoughtco.com/smith-name-meaning-and-origin-1422624" data-component="link" data-source="inlineLink" data-type="internalLink" data-ordinal="2">Smith</a></p></td>
<td><p><a href="https://www.thoughtco.com/english-surnames-meanings-and-origins-1422405" data-component="link" data-source="inlineLink" data-type="internalLink" data-ordinal="3">English</a></p></td>
<td><p>2,442,977</p></td>
</tr>'''
replaces={'James':r'(\w+)','Mary':r'(\w+)',
          '4,735,694':'[0-9\,]+',
          '3,265,105':'[0-9\,]+',
          '1':'\d+'}
matcher_names=re.compile(
    reduce(
        lambda acc,item:acc.replace(*item),
        replaces.items(),
    re.sub(
    r'(?:\\\ )+',
    lambda m:r'\s*',
    re.escape(s)
    )
        ),re.M|re.S
    )
assert matcher_names.match(s)

data=[]
for url in urls:
    with urllib.request.urlopen(url) as fp:
        data.append(fp.read())
names_html=data[0].decode()
assert len(names:=matcher_names.findall(names_html))

s='data-component="link" data-source="inlineLink" data-type="internalLink" data-ordinal="2"'
data_=re.findall(r'data\-(\w+)="\w+"',s)
matcher_data_=r'data\-(?:%s)="\w+"'%('|'.join(data_))
matcher_a=r'(?:(?:href="%s[-a-z0-9]+"|%s)\s*)+'%(
    re.escape('https://www.thoughtco.com/'),matcher_data_
    )
assert re.findall(matcher_a,s_last_names)
matcher_last_names=re.compile(r'\s*'.join(
    ['<tr>']+
    ['<td>(?:<p>)?%s(?:</p>)?</td>'%i for i in [#7,11 (?:<(?:|/)p>)?
        r'(\d+)\s*',#3 \s*
        r'(?:<a\s+%s>(\w+)</a>|(\w+))\s*'%matcher_a,#4 \s*
        r'(?:(?:<a\s+%s>\w+</a>|\w+)(?:,\s*)?)+\s*'%matcher_a,#6 2nd \s*
        r'[0-9,]+'
        ]]+
    ['</tr>']
    ),re.M)
last_names=matcher_last_names.findall(data[1].decode())
last_names=[i[1] or i[2] for i in last_names]
names=[j for i in names for j in i]
assert len(set(last_names))==100
assert len(set(names))==200
assert (set(type(i) for i in names))==set([type('')])

format_str=(lambda x,indent,n:
            '\n'.join(' '*indent+(
                ' '.join('"%s",'%j for j in i)
                )
                       for i in
(lambda x,n:reduce(
    lambda acc,item:(
        acc[:-1]+[acc[-1]+[item]]
        if sum(len(i)+4 for i in acc[-1]+[item])<=n else
        acc+[[item]]
        ),
    x,[[]]
    ))(x,n-indent)
            )
            )

with open('Names.php','wt') as fp:
    fp.write(
r'''<?php


namespace Names;


class Names {
    public $firstNames=[
%s
];
    public $lastNames=[
%s
];
}
'''%tuple(format_str(i,8,81) for i in (names,last_names))
        )
