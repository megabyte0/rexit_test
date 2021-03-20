### Overview
It's netpeak test assignment for php back-end engineer vacancy.  
Mostly built in docker mysql:8 + php:7.4.14-cli

### How to use it
1. First, [install docker](https://docs.docker.com/get-started/#download-and-install-docker-desktop) and `docker-compose` and test it works.
2. Then clone the repository
3. Optionally, remove the comment sign `#` from the front of the line `#RUN php seed.php` 
   in `Dockerfile` to have the database populated with data.
4. Run `docker-compose up --build` in the cloned directory.  
You will see docker images download/build steps, then lots of mysql docker container output and 
`PHP 7.4.14 Development Server (http://0.0.0.0:8000) started` -- this means all works fine.
5. Connect to [`http://localhost:8000`](http://localhost:8000/) with your favorite browser
and see all things working.
6. Enjoy
