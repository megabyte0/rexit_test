### Overview
It's [RexIT test assignment](https://docs.google.com/document/d/1pyLGfuZ_MTUZvGVuDrTe2Zj7pEz_axBSF8B-Bk68DEY/edit)
for [php back-end engineer vacancy](https://www.work.ua/ru/jobs/4285108/).  
Mostly built in docker mysql:8 + php:7.4.14-cli

### How to use it
1. First, [install docker](https://docs.docker.com/get-started/#download-and-install-docker-desktop) and `docker-compose` and test it works.
2. Then clone the repository
3. Run `docker-compose up --build` in the cloned directory.  
You will see docker images download/build steps, then lots of mysql docker container output and 
`PHP 7.4.14 Development Server (http://0.0.0.0:8000) started` -- this means all works fine.

[comment]: <> (4. Connect to [`http://localhost:8000`]&#40;http://localhost:8000/&#41; with your favorite browser)

[comment]: <> (and see all things working.)

[comment]: <> (5. Enjoy)

### API endpoints
There are three API endpoints in the project:

`/api/data/?`<filter_string>, where <filter_string> is an arbitrary combination
of key-value pairs, joined by `&`, each key-value pair joined by `=`, where the keys
can be:  
`category_id` -- id of a single category to display,  
`firstname_like` --   
`lastname_like` --   
`email_like` --   
`gender_id` -- id of gender, presumably `1` or `2`,  
`limit` -- page size, in items (i.e. clients) for pagination,  
`offset` -- first page offset in items from the very beginning, for pagination,  
`age` -- client's age,  
`bday` -- day of birth of clients to select,  
`bmonth` -- month of birth,  
`byear` -- year of birth,  
`min_age`, `max_age` -- for age interval,  
example: `/api/data/?bday=29&bmonth=2&gender_id=1&min_age=25&max_age=30`,

`/api/csv/?`<filter_string> with the same <filter_string> as in `/api/data/?`<filter_string>,
but providing a csv result instead,

`/api/dictionaries` -- collected possible values for front-end `<select>` elements
to be used for filters.

### Why this way?
1. The framework. The framework is coded 100% from scratch. It's much more convenient
   to have some kind of MVC abstraction with classes
   after working with laravel framework for a while. Although, there are no views,
   just `index.html` and the API endpoints in the controller.
2. Why vanilla js for generating HTML DOM elements? But why not? 
   It's fast (I mean runtime) and easy to write, although it lacks readability and
   maintainability.
3. Do we really need the `age` column in resulting `/api/data/?`... response? No.
   It's left just for testing.
4. What are the python sources doing here in php project? Nothing, the back-end
   was initially written in python for convenience and testing MySQL queries, but
   after re-writing the things into php the python sources are not needed and can 
   be deleted with no risk/doubt. They are just left here, for comparison maybe?
   It's related to the question about 'is php dead?' maybe.
   
### Is php dead?
Why do you need this question *at all*? To filter out the candidates by their favourite 
language? Yes, there are a lot of php vacancies, and a tiny portion of 
technologically clean languages ones.
Seriously, it's dead, it was born dead. No language with variables starting only with a 
buck character, with no multiple inheritance, built for only one purpose -- Personal
Home Page -- should never exist. Yes, it's evolving, there's a lot of synthetic sugar
in php 8 (I've read just recently) -- C++-like fields constructor declarations,
kotlin-like null-safe calls (but the thing `?->` even looks ugly in comparison to kotlin
`?.`, `!.`, `!!.` -- a couple more of such language "upgrades" and it will end up in 
a `--\-<{@`-thing )) ), 
even a JIT compilation and much more. Upgrading the language this way will end up
in php 10 (or php "x") being C++ with lots of unneeded built-in functions.

Yes, php is mostly used in xUSSR space, and maybe in some other non-xUSSR projects,
but there are lots of web-frameworks for high-level general-purpose programming
languages, like asp for .net, jsp and Spring for java, django and flask for python,
ruby on rails, even gin for golang and back-end node.js for javascript, etc, etc. 
There's no need for php indeed. Point.