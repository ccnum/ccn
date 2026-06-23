@echo off
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../bin/spipmu
php "%BIN_TARGET%" %*