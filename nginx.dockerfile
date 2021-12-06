FROM nginx:stable-alpine

ENV NGINXUSER=webuser
ENV NGINXGROUP=bloembraaden

RUN mkdir -p /var/www/bloembraaden/site/

# SSL cert https://stackoverflow.com/questions/44047315/generate-a-self-signed-certificate-in-docker
RUN apk update && \
    apk add --no-cache openssl && \
    openssl req -x509 -nodes -days 365 \
    -subj  "/C=CA/ST=QC/O=Company Inc/CN=bloembraaden.io" \
     -addext "subjectAltName=DNS:bloembraaden.io" \
     -newkey rsa:2048 -keyout /etc/ssl/private/nginx-selfsigned.key \
     -out /etc/ssl/certs/nginx-selfsigned.crt;

ADD nginx.conf /etc/nginx/conf.d/default.conf

RUN sed -i "s/user www-data/user ${NGINXUSER}/g" etc/nginx/nginx.conf

RUN adduser -g ${NGINXGROUP} -s /bin/sh -D ${NGINXUSER}