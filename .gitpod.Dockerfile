FROM gitpod/workspace-base:latest

USER root

RUN apt-get update && apt-get -y install apache2

RUN echo "!include /workspace/lamp/mysql/mysql.cnf" > /etc/mysql/my.cnf

RUN mkdir /var/run/mysqld
RUN chown gitpod:gitpod /var/run/apache2 /var/lock/apache2 /var/run/mysqld

RUN sudo sed -i 's/^bind-address.*/#&/' /etc/mysql/mysql.conf.d/mysqld.cnf

RUN addgroup gitpod www-data
