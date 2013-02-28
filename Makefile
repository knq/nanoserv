include makeopts

ifeq ($(HAVE_STATIC),y)
	DEF_STATIC=-DHAVE_STATIC
endif

ifeq ($(HAVE_NAMESPACE),y)
	DEF_NAMESPACE=-DHAVE_NAMESPACE
endif

ifeq ($(HAVE_CLOSURE),y)
	DEF_CLOSURE=-DHAVE_CLOSURE
endif

MAIN_DEFS=$(DEF_NAMESPACE) $(DEF_STATIC) $(DEF_CLOSURE)
COMPAT_DEFS=$(DEF_STATIC) $(DEF_CLOSURE)

PHPP_BIN='build/phpp.php'
PHPP=$(PHP_BIN) $(PHPP_BIN)
PHPP_FLAGS=-l $(MAIN_DEFS)
PHPP_FLAGS_COMPAT=-l $(COMPAT_DEFS)
DOC_DIR=doc
MAIN_DIR=.
COMPAT_DIR=compat
INSTALL_BIN='install'
INSTALL_DIR_MAIN=$(INSTALL_DIR)/nanoserv
INSTALL_DIR_COMPAT=$(INSTALL_DIR)/nanoserv-compat
INSTALL_DIR_DOC=$(INSTALL_DIR)/doc/nanoserv
INSTALL_DIR_PHPDOC=$(INSTALL_DIR_DOC)/docs
INSTALL_DIR_EXAMPLES=$(INSTALL_DIR_DOC)/examples
INSTALL_FLAGS=

all: compat main documentation all-examples
	@echo ""
	@echo "nanoserv build complete, to install run \"make install\"."
	@echo ""

install: install-compat install-main install-documentation install-examples
	@echo ""
	@echo "nanoserv installation successful."
	@echo ""

install-documentation:
	$(INSTALL_BIN) -d $(INSTALL_DIR_DOC)
	$(INSTALL_BIN) -m 0644 $(MAIN_DIR)/ChangeLog $(INSTALL_DIR_DOC)
	$(INSTALL_BIN) -m 0644 $(MAIN_DIR)/README $(INSTALL_DIR_DOC)
	if [ -d $(DOC_DIR) ]; then mkdir -p $(INSTALL_DIR_PHPDOC); cp -R $(DOC_DIR)/* $(INSTALL_DIR_PHPDOC); fi

install-examples:
	$(INSTALL_BIN) -d $(INSTALL_DIR_EXAMPLES)
	$(INSTALL_BIN) -m 0755 $(MAIN_DIR)/examples/*.php $(INSTALL_DIR_EXAMPLES)

install-main:
	$(INSTALL_BIN) -d $(INSTALL_DIR_MAIN)
	$(INSTALL_BIN) -d $(INSTALL_DIR_MAIN)/handlers
	$(INSTALL_BIN) -d $(INSTALL_DIR_MAIN)/handlers/HTTP
	$(INSTALL_BIN) -d $(INSTALL_DIR_MAIN)/handlers/SMTP
	$(INSTALL_BIN) -d $(INSTALL_DIR_MAIN)/handlers/Syslog
	$(INSTALL_BIN) -d $(INSTALL_DIR_MAIN)/handlers/Telnet
	$(INSTALL_BIN) -d $(INSTALL_DIR_MAIN)/handlers/DHCP
	$(INSTALL_BIN) -d $(INSTALL_DIR_MAIN)/handlers/HTTP/XML_RPC
	$(INSTALL_BIN) -d $(INSTALL_DIR_MAIN)/handlers/HTTP/SOAP
	$(INSTALL_BIN) -d $(INSTALL_DIR_MAIN)/handlers/HTTP/JSON_RPC
	$(INSTALL_BIN) -m 0644 $(MAIN_DIR)/nanoserv.php $(INSTALL_DIR_MAIN)
	$(INSTALL_BIN) -m 0644 $(MAIN_DIR)/handlers/*.php $(INSTALL_DIR_MAIN)/handlers
	$(INSTALL_BIN) -m 0644 $(MAIN_DIR)/handlers/HTTP/*.php $(INSTALL_DIR_MAIN)/handlers/HTTP
	$(INSTALL_BIN) -m 0644 $(MAIN_DIR)/handlers/SMTP/*.php $(INSTALL_DIR_MAIN)/handlers/SMTP
	$(INSTALL_BIN) -m 0644 $(MAIN_DIR)/handlers/Syslog/*.php $(INSTALL_DIR_MAIN)/handlers/Syslog
	$(INSTALL_BIN) -m 0644 $(MAIN_DIR)/handlers/Telnet/*.php $(INSTALL_DIR_MAIN)/handlers/Telnet
	$(INSTALL_BIN) -m 0644 $(MAIN_DIR)/handlers/DHCP/*.php $(INSTALL_DIR_MAIN)/handlers/DHCP
	$(INSTALL_BIN) -m 0644 $(MAIN_DIR)/handlers/HTTP/XML_RPC/*.php $(INSTALL_DIR_MAIN)/handlers/HTTP/XML_RPC
	$(INSTALL_BIN) -m 0644 $(MAIN_DIR)/handlers/HTTP/SOAP/*.php $(INSTALL_DIR_MAIN)/handlers/HTTP/SOAP
	$(INSTALL_BIN) -m 0644 $(MAIN_DIR)/handlers/HTTP/JSON_RPC/*.php $(INSTALL_DIR_MAIN)/handlers/HTTP/JSON_RPC

install-compat:
	$(INSTALL_BIN) -d $(INSTALL_DIR_COMPAT)
	$(INSTALL_BIN) -d $(INSTALL_DIR_COMPAT)/handlers
	$(INSTALL_BIN) -m 0644 $(COMPAT_DIR)/nanoserv.php $(INSTALL_DIR_COMPAT)
	$(INSTALL_BIN) -m 0644 $(COMPAT_DIR)/handlers/*.php $(INSTALL_DIR_COMPAT)/handlers

documentation:
	if [ "$(PHPDOC_BIN)" != "" ]; then $(PHPDOC_BIN) -f $(COMPAT_DIR)/nanoserv.php,$(COMPAT_DIR)/handlers/*.php -t $(DOC_DIR) -dn nanoserv -o HTML:Smarty:PHP -ti "nanoserv documentation" -pp off -s on -q; fi

all-examples:
	for FILENAME in `ls -1 examples/*.phpp`; do EX_FILENAME=`echo $$FILENAME| sed -e 's/.phpp/.php/g'`; EX_FILENAME=`basename $$EX_FILENAME`; $(PHPP) $(PHPP_FLAGS) -DPHP_BIN=$(PHP_BIN) -o $(MAIN_DIR)/examples/$$EX_FILENAME $$FILENAME; chmod 755 $(MAIN_DIR)/examples/$$EX_FILENAME; done

main: all-handlers core

compat: all-handlers-compat core-compat

core:
	$(PHPP) $(PHPP_FLAGS) -o $(MAIN_DIR)/nanoserv.php nanoserv.phpp

all-handlers:
	$(PHPP) $(PHPP_FLAGS) -o $(MAIN_DIR)/handlers/DHCP/Server.php handlers/DHCP/Server.phpp
	$(PHPP) $(PHPP_FLAGS) -o $(MAIN_DIR)/handlers/HTTP/Server.php handlers/HTTP/Server.phpp
	$(PHPP) $(PHPP_FLAGS) -o $(MAIN_DIR)/handlers/HTTP/XML_RPC/Server.php handlers/HTTP/XML_RPC/Server.phpp
	$(PHPP) $(PHPP_FLAGS) -o $(MAIN_DIR)/handlers/HTTP/XML_RPC/Persistent_Server.php handlers/HTTP/XML_RPC/Persistent_Server.phpp
	$(PHPP) $(PHPP_FLAGS) -o $(MAIN_DIR)/handlers/HTTP/XML_RPC/Direct_Server.php handlers/HTTP/XML_RPC/Direct_Server.phpp
	$(PHPP) $(PHPP_FLAGS) -o $(MAIN_DIR)/handlers/HTTP/SOAP/Server.php handlers/HTTP/SOAP/Server.phpp
	$(PHPP) $(PHPP_FLAGS) -o $(MAIN_DIR)/handlers/HTTP/SOAP/Persistent_Server.php handlers/HTTP/SOAP/Persistent_Server.phpp
	$(PHPP) $(PHPP_FLAGS) -o $(MAIN_DIR)/handlers/HTTP/SOAP/Direct_Server.php handlers/HTTP/SOAP/Direct_Server.phpp
	$(PHPP) $(PHPP_FLAGS) -o $(MAIN_DIR)/handlers/HTTP/JSON_RPC/Server.php handlers/HTTP/JSON_RPC/Server.phpp
	$(PHPP) $(PHPP_FLAGS) -o $(MAIN_DIR)/handlers/HTTP/JSON_RPC/Persistent_Server.php handlers/HTTP/JSON_RPC/Persistent_Server.phpp
	$(PHPP) $(PHPP_FLAGS) -o $(MAIN_DIR)/handlers/HTTP/JSON_RPC/Direct_Server.php handlers/HTTP/JSON_RPC/Direct_Server.phpp
	$(PHPP) $(PHPP_FLAGS) -o $(MAIN_DIR)/handlers/Line_Input_Connection.php handlers/Line_Input_Connection.phpp
	$(PHPP) $(PHPP_FLAGS) -o $(MAIN_DIR)/handlers/SMTP/Server.php handlers/SMTP/Server.phpp
	$(PHPP) $(PHPP_FLAGS) -o $(MAIN_DIR)/handlers/Syslog/Server.php handlers/Syslog/Server.phpp
	$(PHPP) $(PHPP_FLAGS) -o $(MAIN_DIR)/handlers/Telnet/Connection.php handlers/Telnet/Connection.phpp
	$(PHPP) $(PHPP_FLAGS) -o $(MAIN_DIR)/handlers/Telnet/Line_Input_Connection.php handlers/Telnet/Line_Input_Connection.phpp

core-compat:
	$(PHPP) $(PHPP_FLAGS_COMPAT) -o $(COMPAT_DIR)/nanoserv.php nanoserv.phpp

all-handlers-compat:
	$(PHPP) $(PHPP_FLAGS_COMPAT) -o $(COMPAT_DIR)/handlers/NS_DHCP_Handler.php handlers/DHCP/Server.phpp
	$(PHPP) $(PHPP_FLAGS_COMPAT) -o $(COMPAT_DIR)/handlers/NS_HTTP_Service_Handler.php handlers/HTTP/Server.phpp
	$(PHPP) $(PHPP_FLAGS_COMPAT) -o $(COMPAT_DIR)/handlers/NS_XML_RPC_Service_Handler.php handlers/HTTP/XML_RPC/Server.phpp
	$(PHPP) $(PHPP_FLAGS_COMPAT) -o $(COMPAT_DIR)/handlers/NS_Persistent_XML_RPC_Service_Handler.php handlers/HTTP/XML_RPC/Persistent_Server.phpp
	$(PHPP) $(PHPP_FLAGS_COMPAT) -o $(COMPAT_DIR)/handlers/NS_Direct_XML_RPC_Service_Handler.php handlers/HTTP/XML_RPC/Direct_Server.phpp
	$(PHPP) $(PHPP_FLAGS_COMPAT) -o $(COMPAT_DIR)/handlers/NS_SOAP_Service_Handler.php handlers/HTTP/SOAP/Server.phpp
	$(PHPP) $(PHPP_FLAGS_COMPAT) -o $(COMPAT_DIR)/handlers/NS_Persistent_SOAP_Service_Handler.php handlers/HTTP/SOAP/Persistent_Server.phpp
	$(PHPP) $(PHPP_FLAGS_COMPAT) -o $(COMPAT_DIR)/handlers/NS_Direct_SOAP_Service_Handler.php handlers/HTTP/SOAP/Direct_Server.phpp
	$(PHPP) $(PHPP_FLAGS_COMPAT) -o $(COMPAT_DIR)/handlers/NS_JSON_RPC_Service_Handler.php handlers/HTTP/JSON_RPC/Server.phpp
	$(PHPP) $(PHPP_FLAGS_COMPAT) -o $(COMPAT_DIR)/handlers/NS_Persistent_JSON_RPC_Service_Handler.php handlers/HTTP/JSON_RPC/Persistent_Server.phpp
	$(PHPP) $(PHPP_FLAGS_COMPAT) -o $(COMPAT_DIR)/handlers/NS_Direct_JSON_RPC_Service_Handler.php handlers/HTTP/JSON_RPC/Direct_Server.phpp
	$(PHPP) $(PHPP_FLAGS_COMPAT) -o $(COMPAT_DIR)/handlers/NS_Line_Input_Connection_Handler.php handlers/Line_Input_Connection.phpp
	$(PHPP) $(PHPP_FLAGS_COMPAT) -o $(COMPAT_DIR)/handlers/NS_SMTP_Service_Handler.php handlers/SMTP/Server.phpp
	$(PHPP) $(PHPP_FLAGS_COMPAT) -o $(COMPAT_DIR)/handlers/NS_Syslog_Handler.php handlers/Syslog/Server.phpp
	$(PHPP) $(PHPP_FLAGS_COMPAT) -o $(COMPAT_DIR)/handlers/NS_Telnet_Handler.php handlers/Telnet/Connection.phpp
	$(PHPP) $(PHPP_FLAGS_COMPAT) -o $(COMPAT_DIR)/handlers/NS_Telnet_Line_Input_Handler.php handlers/Telnet/Line_Input_Connection.phpp

clean: clean-main clean-compat clean-documentation clean-examples

clean-main:
	find $(MAIN_DIR)/handlers/ |grep ".php$$" |xargs rm -f
	rm -f $(MAIN_DIR)/nanoserv.php

clean-compat:
	find $(COMPAT_DIR)/ |grep ".php$$" |xargs rm -f

clean-documentation:
	rm -rf $(DOC_DIR)

clean-examples:
	rm -f examples/*.php
