FROM ipeos/spip:latest

COPY ./config/_config_cas.php /usr/src/spip/config/_config_cas.php
COPY ./plugins /usr/src/spip/plugins/
COPY ./docker-entrypoint.sh /

RUN chmod +x /docker-entrypoint.sh

ENTRYPOINT ["/docker-entrypoint.sh"]

CMD ["apache2-foreground"]