#!/bin/bash
find /etc/apache2/mods-enabled -name 'mpm_*' -delete
ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load
[ -f /etc/apache2/mods-available/mpm_prefork.conf ] && \
    ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf
exec apache2-foreground
