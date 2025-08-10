#!/usr/bin/env bash
set -euo pipefail

# Install PHP extensions, configure nginx/apache vhost to backend/public, set file permissions.
# Ensure storage and bootstrap/cache are writable.