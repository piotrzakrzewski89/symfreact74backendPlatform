FROM nginx:stable-perl

WORKDIR /app

RUN \
    rm -f /etc/localtime \
    && ln -s /usr/share/zoneinfo/Europe/Warsaw /etc/localtime

RUN rm /etc/nginx/conf.d/*

ADD ./conf.d/ /etc/nginx/conf.d/
