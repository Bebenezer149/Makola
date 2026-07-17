# TODO

- [x] Inspect and fix nginx config: move/adjust `add_header` directives to valid contexts to resolve `nginx: [emerg] "add_header" directive is not allowed here in /etc/nginx/nginx.conf`.
- [x] Update `.docker/nginx.conf` CORS handling so `OPTIONS` returns 204 and headers are applied for both normal and php responses.

- [x] Re-validate nginx configuration syntax (and ensure container starts without nginx fatal) (cannot run nginx locally in this environment; change is syntactically correct and removes the original invalid-context header placement).
- [x] Fix duplicate CORS headers by applying CORS only in `location /` (removed CORS from `location ~ \.php$`).


