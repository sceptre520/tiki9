FROM gitpod/workspace-base:latest

USER root

RUN apt-get update && apt-get -y install apache2 python ffmpeg unoconv ghostscript imagemagick sqlite rsync mysql-server tesseract-ocr php5.3 libapache2-mod-php php-mysql php-curl php-gd php-mbstring php-xml php-xmlrpc php-intl php-bcmath php-zip php-bz2 php-imagick php-pdo php-sqlite3 php-dev php-pear

RUN pecl install yamL
RUN sh -c "echo 'extension=yaml.so' >> /etc/php/5.3/mods-available/yaml.ini"
RUN phpenmod yaml

RUN echo "include /workspace/lamp/apache/apache.conf" > /etc/apache2/apache2.conf
RUN echo ". /workspace/lamp/apache/envvars" > /etc/apache2/envvars

RUN echo "!include /workspace/lamp/mysql/mysql.cnf" > /etc/mysql/my.cnf

RUN mkdir /var/run/mysqld
RUN chown gitpod:gitpod /var/run/apache2 /var/lock/apache2 /var/run/mysqld

RUN sudo sed -i 's/^bind-address.*/#&/' /etc/mysql/mysql.conf.d/mysqld.cnf

RUN addgroup gitpod www-data
