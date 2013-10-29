ufsdump 0ufs - `ufsdump 0fS - /` / | compress - | ssh cmhlog001 \
"cd /sparky; /usr/bin/uncompress - | /usr/sbin/ufsrestore -xf -"

ufsdump 0ufs - `ufsdump 0fS - /usr` /usr | compress - | ssh cmhlog001 \
"cd /sparky/usr; /usr/bin/uncompress - | /usr/sbin/ufsrestore -xf -"

ufsdump 0ufs - `ufsdump 0fS - /home` /home | compress - | ssh cmhlog001 \
"cd /sparky/home; /usr/bin/uncompress - | /usr/sbin/ufsrestore -xf -"

ufsdump 0ufs - `ufsdump 0fS - /opt` /opt | compress - | ssh cmhlog001 \
"cd /sparky/opt; /usr/bin/uncompress - | /usr/sbin/ufsrestore -xf -"

ufsdump 0ufs - `ufsdump 0fS - /root` /root | compress - | ssh cmhlog001 \
"cd /sparky/root; /usr/bin/uncompress - | /usr/sbin/ufsrestore -xf -"

ufsdump 0ufs - `ufsdump 0fS - /usr/local` /usr/local | compress - | \
ssh cmhlog001 "cd /sparky/usr/local; /usr/bin/uncompress - | \
/usr/sbin/ufsrestore -xf -"
