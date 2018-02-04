CURRENT_DIR = $(shell pwd)
UTILS_PATH = $(shell cd $(CURRENT_DIR)/../utils; pwd)
PHP_PATH = $(shell cd $(CURRENT_DIR)/../app; pwd)
CACHE_DIR = $(shell cd $(CURRENT_DIR)/../cache; pwd)
PHP_FILES = $(shell find $(PHP_PATH) -type f -name '*.php') 
PO_FILES = $(shell find $(CURRENT_DIR) -type f -name 'messages.po')
MO_FILES = $(shell find $(CURRENT_DIR) -type f -name 'messages.po' | sed s/\.po/\.mo/)
TMP_FILE = /tmp/files_to_translate.txt 
TMP_PO_FILE = /tmp/messages.po
BASE_POT = /tmp/base_message.pot
OLD_BASE_POT = /tmp/old_base_message.pot
HTTPD_USER_AND_GROUP = $(shell stat -c "%U.%G" $(CACHE_DIR))
all: $(MO_FILES)
CACHE_UPDATED = /tmp/cache-updated

all: $(CACHE_UPDATED) $(MO_FILES)

$(CACHE_UPDATED): $(PHP_FILES)
	@echo Rebuilding cache
	touch $(CACHE_UPDATED)
	$(shell rm -rf $(CACHE_DIR); mkdir $(CACHE_DIR); chown -R $(HTTPD_USER_AND_GROUP) $(CACHE_DIR); php $(UTILS_PATH)/make_php_cache_files.php; chown -R $(HTTPD_USER_AND_GROUP) $(CACHE_DIR))

clean:
	@echo Clean!
	rm $(TMP_FILE)
	rm $(BASE_POT)
	rm $(CACHE_UPDATED)

$(TMP_FILE): $(PHP_FILES) $(CACHE_UPDATED)
	@echo Writting $(TMP_FILE)
	$(shell cd $(CURRENT_DIR); find .. -type f -name '*.php' > $(TMP_FILE))

$(BASE_POT): $(TMP_FILE)
	@echo Searching for translations...
	$(shell cd $(CACHE_DIR); xgettext --omit-header --from-code=UTF-8 -n -L PHP -o $(BASE_POT) -f $(TMP_FILE))

$(PO_FILES) : $(BASE_POT)
	@echo Merging...
	msgmerge -s $@ $(BASE_POT) -o $(TMP_PO_FILE)
	mv $(TMP_PO_FILE) $@

$(MO_FILES) : $(PO_FILES)
	@echo Producing $@
	msgfmt $(subst .mo,.po,$@) -c -o $@

