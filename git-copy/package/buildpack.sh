#!/bin/bash

CURRENT_DIR=`pwd`
CURRENT_DIR_PARENT=`dirname $CURRENT_DIR`
PARENT_DIR=`dirname $CURRENT_DIR_PARENT`
PACK_DIR=`dirname $PARENT_DIR`
VERSION=`cat version|head -1`
PACKAGE=`cat packname`
PACKNAME="$PACKAGE"_"$VERSION"
INSTALL_FILE="$PACKAGE.install"

echo $PARENT_DIR
echo $CURRENT_DIR_PARENT
echo $PACK_DIR
echo $VERSION
echo $PACKAGE
echo $PACKNAME
echo $INSTALL_FILE

#exit

cd $PARENT_DIR

echo `pwd`
echo "Building Package: $PACKNAME"

dh_make --createorig -p $PACKNAME -e gosiya.kartik@capillarytech.com


if [ -d "debian" ]; then
  echo "dh_make done successfully.. proceeding"
fi	

cd $CURRENT_DIR

echo "copying install and changelog"
cp $INSTALL_FILE $PARENT_DIR/debian/
#cp changelog $PARENT_DIR/debian/

echo "Moving to parent directory and building package"
cd $PARENT_DIR

dpkg-buildpackage -us -uc

echo "Removing all other garbage files"
pwd
cd $PACK_DIR
pwd
for i in `ls "$PACKAGE"_"*" | grep -v deb` 
do
  echo "Removing $i"
  #rm -f $i
done

echo "package created..moving to pack_dir"
mv *.deb $CURRENT_DIR

cd $PARENT_DIR
#rm -rf debian

echo "Done.. package has been built !"

