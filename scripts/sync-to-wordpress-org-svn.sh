#!/usr/bin/env bash
# Sync this plugin into a local WordPress.org SVN working copy (trunk + assets).
# Prereq: svn checkout https://plugins.svn.wordpress.org/lancedesk-responsive-menu-for-elementor/ <DIR>
# Usage: ./scripts/sync-to-wordpress-org-svn.sh /path/to/svn/checkout
#
# Then: cd /path/to/svn/checkout && svn status && svn add --force trunk assets && svn commit

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
SVN_ROOT="${1:?Usage: $0 /path/to/lancedesk-responsive-menu-for-elementor-svn-checkout}"
PLUGIN_BASE="$(basename "${PLUGIN_ROOT}")"

if [[ ! -d "${SVN_ROOT}/trunk" ]] || [[ ! -d "${SVN_ROOT}/assets" ]]; then
  echo "error: expected ${SVN_ROOT}/trunk and ${SVN_ROOT}/assets (run svn checkout first)." >&2
  exit 1
fi

echo "==> Syncing plugin -> trunk (excludes .svn, VCS, GitHub, directory-assets, nested SVN dir \"${PLUGIN_BASE}\")"

if command -v rsync >/dev/null 2>&1; then
  rsync -a --delete \
    --exclude '.svn/' \
    --exclude '.git/' \
    --exclude '.github/' \
    --exclude 'directory-assets/' \
    --exclude '.distignore' \
    --exclude 'README.md' \
    --exclude 'scripts/' \
    --exclude 'docs/' \
    --exclude "${PLUGIN_BASE}/" \
    "${PLUGIN_ROOT}/" "${SVN_ROOT}/trunk/"
elif command -v robocopy >/dev/null 2>&1 && command -v cygpath >/dev/null 2>&1; then
  PLUGIN_WIN="$(cygpath -w "${PLUGIN_ROOT}")"
  TRUNK_WIN="$(cygpath -w "${SVN_ROOT}/trunk")"
  export MSYS2_ARG_CONV_EXCL='*'
  set +e
  robocopy "${PLUGIN_WIN}" "${TRUNK_WIN}" /MIR /E \
    /XD .svn .git .github directory-assets scripts docs "${PLUGIN_BASE}" \
    /XF README.md .distignore /NFL /NDL /NJH /NJS /NC /NS /NP
  RC=$?
  set -e
  if [[ "${RC}" -ge 8 ]]; then
    echo "error: robocopy failed (exit ${RC})." >&2
    exit 1
  fi
else
  echo "error: need rsync, or Git Bash with robocopy + cygpath." >&2
  exit 1
fi

echo "==> Copying directory-assets -> assets/ (flat: banner, icon, screenshot-*.png)"
cp -f "${PLUGIN_ROOT}/directory-assets/banner-772x250.png" "${SVN_ROOT}/assets/"
# Optional HiDPI banner (1544x500); sharp on retina / wide layouts. Add file to directory-assets/ when ready.
if [[ -f "${PLUGIN_ROOT}/directory-assets/banner-1544x500.png" ]]; then
  cp -f "${PLUGIN_ROOT}/directory-assets/banner-1544x500.png" "${SVN_ROOT}/assets/"
fi
cp -f "${PLUGIN_ROOT}/directory-assets/icon-256x256.png" "${SVN_ROOT}/assets/"
shopt -s nullglob
for f in "${PLUGIN_ROOT}/directory-assets/screenshots/"*.png; do
  cp -f "$f" "${SVN_ROOT}/assets/$(basename "$f")"
done

echo ""
echo "Done. Next (from your machine, with SVN installed):"
echo "  cd \"${SVN_ROOT}\""
echo "  svn status"
echo "  svn add --force trunk assets"
echo "  svn commit -m \"Release 1.0.10 — sync trunk and plugin directory assets\""
echo ""
echo "If this is the first tagged build for this version on .org:"
echo "  svn cp trunk tags/1.0.10"
echo "  svn commit -m \"Tag 1.0.10\""
