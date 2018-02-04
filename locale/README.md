
# Utility to create language files #

## To create new language ##

1. Add a new directory with country ID. **mkdir ES**
1. Copy the header file to the directory. **cp header.po ES/messages.po**
1. Create the directories to hold the rest of the files to translate. **mkdir ES/files; mkdir ES/html**
1. Update cache. **make update-cache**
1. Update message files. **make**
1. Edit the po file and translate it. **vi ES/messages.po**
1. Update message files. **make**

