import re
from collections import Counter
import urllib.request
from format_str import format_str
assert ''.join(i for i in (
    chr(i) for j in [
        (32,ord('A')),
        (ord('Z')+1,ord('a')),
        (ord('z')+1,128)
        ] for i in range(*j)
    ) if re.escape(i)!=i)==' #$&()*+-.?[\\]^{|}~'
##fn='words.html'
##with open(fn,'rt') as fp:
##    s=fp.read()
url='https://www.talkenglish.com/vocabulary/top-2000-vocabulary.aspx'
with urllib.request.urlopen(url) as fp:
    s=fp.read()
s=s.decode()
#x=matcher.findall(s)
#print(len(x))
find=lambda s,substr:(
    lambda s,n:s[s.rindex('<tr>',0,n):s.index('</tr>',n)+5])(
        s,s.index(substr)
        )
assert '5220' in s
x=find(s,'5220')
x_=re.sub(r'(?:\\\t|\\\n|\\\ |\\\r){2,}',lambda m:r'\s+',re.escape(x))
assert re.match(x_,x,re.M)
x__=(x_.replace('of','(.*?)')
       .replace('preposition',r'((?:[a-z]+?\,?\s*)+)')
       .replace('5220',r'\d+')
     )
assert re.match(x__,x,re.M)
assert len(x:=re.findall(x__,s))
matcher=re.compile((
    re.escape(r'''<a href="/how-to-use/the" target="_blank">the</a>''')
    .replace('the',r'(.*?)')
    ))
#print(
c=Counter((matcher.match(i)!=None,'<' in i) for i,parts_of_speech in x)
#)
assert all(i==j for i,j in c)
x=[((m.group(2) if (m:=matcher.match(i)) else i),
    [j.strip() for j in parts_of_speech.split(',')])
   for i,parts_of_speech in x]
assert len(x)==2265

words=[w for w,ps in x
       if set(ps)&set({
           'noun': 1592,
           'verb': 1109,
           'adjective': 610,
           'adverb': 313,
           'idiom': 129})!=set() and w not in 'a,the'.split(',')
       ]
#print(x)
#with open('words.js','wt') as fp:
#    fp.write('var words=%s;'%x)
assert False
with open('Words.php','wt') as fp:
    fp.write(
r'''<?php


namespace Words;


class Words {
    public $words=[
%s
];
}
'''%tuple(format_str(i,8,81) for i in (words,))
        )
