FROM alpine:latest
RUN apk update
RUN apk upgrade
RUN apk add --no-cache ca-certificates

MAINTAINER alturismo alturismo@gmail.com

# Extras
RUN apk add --no-cache curl php

# Install Python3 and owi2plex
RUN apk add --no-cache python3 py3-pip libxml2 libxml2-dev
RUN apk add --update --no-cache g++ libxslt-dev python3-dev
RUN rm /usr/lib/python3.11/EXTERNALLY-MANAGED
RUN pip install click==8.1.7 requests==2.31.0 lxml==5.1.0 pyyaml==6.0.1 twine==5.0.0 future==1.0.0
ADD https://raw.githubusercontent.com/cvarelaruiz/owi2plex/master/owi2plex.py /usr/bin/owi2plex.py
ADD https://raw.githubusercontent.com/cvarelaruiz/owi2plex/master/version.py /usr/bin/version.py
RUN chmod +x /usr/bin/owi2plex.py

# Timezone (TZ)
RUN apk update && apk add --no-cache tzdata
ENV TZ=Europe/Berlin
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Add Bash shell & dependancies
RUN apk add --no-cache bash busybox-suid su-exec

# Volumes
VOLUME /config
VOLUME /guide2go
VOLUME /owi2plex
VOLUME /zap
VOLUME /phpserver
VOLUME /root/.xteve
VOLUME /tmp/xteve

# Add ffmpeg and vlc
RUN apk add --no-cache vlc g++ libva-intel-driver make libass-dev libbluray-dev intel-media-sdk-dev intel-media-driver x264-dev x265-dev
RUN sed -i 's/geteuid/getppid/' /usr/bin/vlc
RUN cd /tmp && wget https://www.ffmpeg.org/releases/ffmpeg-6.1.tar.xz && tar xJf ffmpeg-6.1.tar.xz && cd ffmpeg-6.1 && /tmp/ffmpeg-6.1/configure --arch=x86_64 --disable-yasm --enable-vaapi --enable-libass --enable-libmfx --enable-libbluray --enable-nonfree --enable-gpl --enable-libx264 --enable-libx265 && make -j8 && make install

RUN apk del g++ make

# Add zap
RUN apk add --no-cache perl perl-http-cookies perl-lwp-protocol-https perl-json perl-json-xs																						
ADD zap2xml.pl /usr/bin/zap2xml.pl

# Add guide2go
ADD guide2go /usr/bin/guide2go

# Add xTeve
RUN wget https://github.com/xteve-project/xTeVe-Downloads/raw/master/xteve_linux_amd64.zip -O temp.zip; unzip temp.zip -d /usr/bin/; rm temp.zip

# Add Basics
ADD cronjob.sh /
ADD entrypoint.sh /
ADD sample_cron.txt /
ADD sample_xteve.txt /
ADD sample_php.txt /
ADD index.php /
ADD watch.sh /

# Set executable permissions
RUN chmod +x /entrypoint.sh
RUN chmod +x /cronjob.sh
RUN chmod +x /usr/bin/xteve
RUN chmod +x /usr/bin/zap2xml.pl
RUN chmod +x /usr/bin/guide2go
RUN chmod +x /watch.sh

RUN mkdir -p /root/.xteve && chown -R 99:100 /root/.xteve
# Expose Port
EXPOSE 34400

RUN rm /tmp/ffmpeg-6.1.tar.xz

# Entrypoint
ENTRYPOINT ["./entrypoint.sh"]
