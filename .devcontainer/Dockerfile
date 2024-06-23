FROM mcr.microsoft.com/devcontainers/php:8.2-bookworm
#FROM mcr.microsoft.com/devcontainers/php:8.3-bookworm

# +-----------------------------+
# | METADATA                    |
# +-----------------------------+

LABEL org.opencontainers.image.vendor = "Jens A. Koch"
LABEL org.opencontainers.image.source = "https://github.com/jakoch/php-devbox"

# +-----------------------------+
# | ARGS                        |
# +-----------------------------+

#ARG USERNAME=vscode
ARG USER_UID=1000
ARG USER_GID=$USER_UID

# +-----------------------------+
# | ENV                         |
# +-----------------------------+

ENV DEBIAN_FRONTEND=noninteractive
ENV DEBCONF_NONINTERACTIVE_SEEN=true

# +------------------------------+
# | PRE-REQUISITES/INIT PACKAGES |
# +------------------------------+

# avoid debconf delaying package configuration, since apt-utils is not installed
RUN apt-get update && \
    apt-get -y install --no-install-recommends apt-utils dialog sudo 2>&1

# Install packages
RUN apt-get -y install --no-install-recommends \
    build-essential autoconf \
    git zsh curl wget nano \
    # For PHP Extension: tidy
    libtidy-dev \
    # For PHP Extension: zip
    libzip-dev \
    # For PHP Extension: mbstring requires oniguruma
    libonig-dev \
    # for the old docs
    xsltproc \
    # for composer
    p7zip-full

# Cleanup
RUN apt-get autoremove -fy && \
    apt-get clean && \
    apt-get autoclean -y && \
    rm -rf /var/lib/apt/lists/*

# +-----------------------------+
# | xdebug                      |
# +-----------------------------+

# Important!
# 1) You have to set the env var XDEBUG_TRIGGER=1 to trigger xdebug (e.g. in .vscode/launch.json).
#    https://xdebug.org/docs/all_settings#start_with_request#trigger
# 2) You might use the env var XDEBUG_MODE to override the xdebug.mode.
#    https://xdebug.org/docs/develop#mode

RUN echo "xdebug.mode=develop,debug"               >> /usr/local/etc/php/conf.d/xdebug.ini; \
    echo "xdebug.start_with_request=trigger"       >> /usr/local/etc/php/conf.d/xdebug.ini; \
    echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/xdebug.ini; \
    echo "xdebug.client_port=9003"                 >> /usr/local/etc/php/conf.d/xdebug.ini; \
    echo "xdebug.cli_color=1"                      >> /usr/local/etc/php/conf.d/xdebug.ini; \
    echo "xdebug.discover_client_host=1"           >> /usr/local/etc/php/conf.d/xdebug.ini; \
    export XDEBUG_TRIGGER=1

# https://xdebug.org/docs/compat
#RUN pecl install xdebug
RUN docker-php-ext-enable xdebug

# +------------------------------+
# | Install PHP Extensions       |
# +------------------------------+

RUN docker-php-ext-install opcache
RUN docker-php-ext-install zip
RUN docker-php-ext-install tidy
RUN docker-php-ext-install mbstring

# for phan
#RUN docker-php-ext-install ast
RUN pecl install ast
RUN echo "extension=ast.so" >> /usr/local/etc/php/conf.d/php.ini;

RUN echo "memory_limit=-1" >> /usr/local/etc/php/conf.d/php.ini;
RUN echo "max_execution_time = 0" >> /usr/local/etc/php/conf.d/php.ini;

#RUN ls -al /usr/local/lib/php/extensions/

# +------------------------------+
# | Update Composer              |
# +------------------------------+

RUN composer self-update
RUN composer diagnose

# re-enable dialog mode for apt-get
ENV DEBIAN_FRONTEND=dialog
