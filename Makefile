SHELL := /bin/bash
.ONESHELL:
.SHELLFLAGS := -eu -o pipefail -c

REMOTE ?= origin
BRANCH ?= main
CHANGELOG := CHANGELOG.md
RELEASE_NOTES := release_note.md

VERSION ?= $(word 2,$(MAKECMDGOALS))
$(VERSION):
	@:

.PHONY: release recap prereq ensure-main bump-version changelog tag push gh-release

prereq:
	@command -v git >/dev/null || { echo "git manquant"; exit 1; }
	@command -v gh  >/dev/null || { echo "gh (GitHub CLI) manquant"; exit 1; }
	@command -v jq  >/dev/null || { echo "jq manquant"; exit 1; }

ensure-main:
	git fetch --tags --quiet
	b=$$(git rev-parse --abbrev-ref HEAD)
	if [[ "$$b" != "$(BRANCH)" ]]; then
	  echo "❌ Branche courante = $$b. Bascule sur $(BRANCH)"; exit 1
	fi
	if ! git diff-index --quiet HEAD --; then
	  echo "❌ Le working tree n'est pas propre (commit/stash d'abord)"; exit 1
	fi

recap:
	git fetch --tags --quiet
	# last = dernier tag joignable; base = BASE (si fournie) sinon last, sinon premier commit
	last=$$(git describe --tags --abbrev=0 2>/dev/null || echo "")
	base=$${BASE:-$${last:-$$(git rev-list --max-parents=0 HEAD)}}
	echo "Commits depuis $$base :"
	git log --pretty="* %s (%h) – %an" "$$base"..HEAD

bump-version:
	test -n "$(VERSION)" || { echo "Usage: make release X.Y.Z"; exit 1; }
	tmp=$$(mktemp)
	jq --arg v "$(VERSION)" 'if has("version") then .version=$$v else . + {"version":$$v} end' composer.json > $$tmp && mv $$tmp composer.json
	git add composer.json
	git commit -m "chore(release): bump version to v$(VERSION)"

changelog:
	git fetch --tags --prune --quiet

	# Calcule la base UNE FOIS et réutilise-la pour les 2 fichiers
	last=$$(git describe --tags --abbrev=0 2>/dev/null || echo "")
	base=$${BASE:-$${last:-$$(git rev-list --max-parents=0 HEAD)}}

	# --- release_note.md (remplacé) ---
	{
	  echo "# v$(VERSION) — $$(date +%F)"
	  echo
	  echo "## Changes"
	  git log --pretty="* %s (%h) – %an" "$$base"..HEAD
	} > "$(RELEASE_NOTES)"

	# --- CHANGELOG.md (on préprend la nouvelle section) ---
	# On réutilise EXACTEMENT la même plage de commits
	{
	  echo "# Changelog"
	  echo
	  echo "## v$(VERSION) — $$(date +%F)"
	  echo
	  git log --pretty="* %s (%h) – %an" "$$base"..HEAD
	  echo
	  # On réattache l'ancien contenu SANS son en-tête "# Changelog"
	  if [ -f "$(CHANGELOG)" ]; then
	    sed '1,/^## /d' "$(CHANGELOG)"
	  fi
	} > "$(CHANGELOG).tmp"
	mv "$(CHANGELOG).tmp" "$(CHANGELOG)"

	git add "$(CHANGELOG)" "$(RELEASE_NOTES)"
	git commit -m "docs(changelog): v$(VERSION)"

tag:
	git tag -a "v$(VERSION)" -m "v$(VERSION)"

push:
	git push "$(REMOTE)" "$(BRANCH)"
	git push "$(REMOTE)" "v$(VERSION)"

gh-release:
	gh release create "v$(VERSION)" \
	  --target "$(BRANCH)" \
	  --title "v$(VERSION)" \
	  --notes-file "$(RELEASE_NOTES)"

release: prereq ensure-main bump-version changelog tag push gh-release
	@echo "✅ Release v$(VERSION) publiée."
