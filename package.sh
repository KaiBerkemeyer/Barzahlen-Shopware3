#!/bin/bash
DST="barzahlen_shopware3_plugin_v1.0.2"
if [ -d $DST ]; then
rm -R $DST
fi
mkdir -p $DST/Frontend/ZerintPaymentBarzahlenSW3
cp -r src/engine/Shopware/Plugins/Local/Frontend/ZerintPaymentBarzahlenSW3/ $DST/Frontend/ZerintPaymentBarzahlenSW3/
zip -r $DST.zip $DST/Frontend/
rm -R $DST