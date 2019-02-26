FROM nginx:alpine

ADD nginx.conf /etc/nginx/
ADD symfony.conf /etc/nginx/conf.d/
ADD upstream.conf /etc/nginx/conf.d/

RUN rm /etc/nginx/conf.d/default.conf

CMD ["nginx", "-g", "daemon off; error_log stderr;"]

EXPOSE 80
EXPOSE 443