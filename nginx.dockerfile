FROM nginx:stable-alpine

ENV NGINXUSER=webuser
ENV NGINXGROUP=bloembraaden

RUN mkdir -p /var/www/bloembraaden/site/

ADD nginx.conf /etc/nginx/conf.d/default.conf

RUN sed -i "s/user www-data/user ${NGINXUSER}/g" etc/nginx/nginx.conf

RUN adduser -g ${NGINXGROUP} -s /bin/sh -D ${NGINXUSER}