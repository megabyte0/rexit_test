### Overview
It's 4limes test assignment for php back-end engineer vacancy.  
Mostly built in docker mysql:8 + php:7.4.14-cli

### How to use it
1. First, [install docker](https://docs.docker.com/get-started/#download-and-install-docker-desktop) and `docker-compose` and test it works.
2. Then clone the repository
3. Run `docker-compose up --build` in the cloned directory.  
You will see docker images download/build steps, then lots of mysql docker container output and 
`PHP 7.4.14 Development Server (http://0.0.0.0:8000) started` -- this means all works fine.
4. Connect to [`http://localhost:8000`](http://localhost:8000/) with your favorite browser
and see all things working.
5. Enjoy

### Why is it done this way?
The rest goes in Russian.  
Во-первых, у меня на машинке практически не стоит php,
потому что php ставится по большинству используя `ppa:ondrej/php`,
которая вместе с php ставит некоторые несовместимые между собой пакеты вместе
(посмотреть, можно, например, [здесь](https://github.com/GPUOpen-Drivers/AMDVLK/issues/159)),
что мешает, например, скомпилировать AMDVLK.  
Поэтому docker, тем более, что это удобно.  

Почему весь бэк-энд состоит по сути из одной функции `/api/data`?  
Нигде в тестовом задании не сказано, что нужно реализовать функционал загрузки части юзеров и части постов.
Вместе, данные о юзерах и постах занимают в json около 23кб (в сжатом виде <8кб),
что по меркам сегодняшнего интернета довольно мало.  
Вся фильтрация происходит на фронте. Где-нибудь сказано, что она должна быть на бэке? Нет.  
Для 1000-10000 пользователей/постов, да, была бы и дозагрузка постов и пагинация пользователей,
но для таких малых объёмов это не требуется.  
Тем более, это (пагинация, например) довольно просто добавляется в контроллер
и параметрами соответствующего метода `MyDbModel`, а запрос к базе становится  
`select * from test.Users limit ?,?;`.  

Так же, я знаю SQL `join` и хорошо умею использовать,
но в данном случае гораздо проще сделать 2 запроса к базе,
пробежаться потом по результату и запихать в поле элемента массива пользователей массив его постов.  

Зачем тогда весь этот мини-фреймворк? Расширяемость. Оказалось, я без фреймворка почти не могу, хотя весь требуемый бэк
(не считая создания таблиц и заполнения данными) можно было вполне уместить в 1 файл 20-30 строк.  

Где в html-документе код bootstrap-вёрстки, все эти `<div class="row"><div class="col-4">` и вот это вот всё?  
Посмотрите метод `renderMain` в js. Всё происходит там. 
Этот код там есть, пусть и в несколько другом виде, и генерируется динамически.

Почему первый раз скрипт отрабатывает (на mysql-базе на hdd, не на sdd) порядка 10 секунд?  
Потому что происходит 100 записей `insert` в таблицу Posts с автокоммитом после каждой.  
В принципе, можно было завернуть все эти записи в отдельную транзакцию, но это было нелогично,
транзакции по большинству предназначены для сохранения целостности связанных данных (поправьте, если я ошибаюсь).  
Почему было не сделать одним `insert` с кучей `values`? 
На неизвестной длине массива Posts нельзя сделать `insert` prepared statement, а почему-то вся `MyDbModel` в принципе
опирается на prepared statements, и когда я сообразил про insert multiple values мне уже было влом переделывать.

Что с Namespace'ами?  
Я просто не умею их правильно использовать пока что, при написании с нуля.

На фронте больше обработки данных, чем на бэке, ты что, фронт-эндщик? В_курсе, что так не должно быть?  
Так получилось, да и как бы задумывалось сделать именно такое api. 
Нет, я бэкер, в том плане, что фронт-энд я пишу раза в 2-3 медленнее, особенно вёрстку.  
В курсе.

### Screenshots
![mobile, initial](https://i.imgur.com/wMZajmU.png) 
![mobile, both filters](https://i.imgur.com/I83ELY7.png)
![mobile, showing posts](https://i.imgur.com/QFEF9Dl.png) 
![mobile, posts next page](https://i.imgur.com/HS5rJVh.png)  
![desktop, showing posts](https://i.imgur.com/RO37Ept.png)
