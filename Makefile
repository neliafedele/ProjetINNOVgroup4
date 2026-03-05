PORT ?= 8000
HOST ?= localhost
WEBROOT := Fichier racines
APP := rak-canteen-pwa.html

.PHONY: help run run-lan run-index

help:
	@echo "Commandes disponibles :"
	@echo "  make run         - Lance la PWA (URL: http://$(HOST):$(PORT)/$(APP))"
	@echo "  make run-lan     - Lance la PWA pour téléphone (LAN, host 0.0.0.0)"
	@echo "  make run-index   - Lance la page index.php (URL: http://$(HOST):$(PORT)/index.php)"
	@echo ""
	@echo "Variables optionnelles :"
	@echo "  PORT=8000 HOST=localhost"

run:
	@echo "Serveur local démarré sur http://$(HOST):$(PORT)/$(APP)"
	@echo "Arrêt: Ctrl+C"
	@php -S $(HOST):$(PORT) -t "$(WEBROOT)"

run-lan:
	@echo "Serveur LAN démarré sur http://0.0.0.0:$(PORT)/$(APP)"
	@echo "Exemple téléphone: http://10.129.195.25:$(PORT)/$(APP)"
	@echo "Arrêt: Ctrl+C"
	@php -S 0.0.0.0:$(PORT) -t "$(WEBROOT)"

run-index:
	@echo "Serveur local démarré sur http://$(HOST):$(PORT)/index.php"
	@echo "Arrêt: Ctrl+C"
	@php -S $(HOST):$(PORT) -t "$(WEBROOT)"
