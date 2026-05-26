# Production smoke tests (sourced by verify-all.sh; do not run ad-hoc checks outside this file).
#
# To add a new check: implement verify_<name>() here and call it from verify_deploy().

VERIFY_URL="${VERIFY_URL:-https://www.bluestarfishguesthouse.com/}"
VERIFY_GREP="${VERIFY_GREP:-Blue Starfish Guest}"
VERIFY_CONTACT_URL="${VERIFY_CONTACT_URL:-https://www.bluestarfishguesthouse.com/contact/}"
VERIFY_LOCATION_URL="${VERIFY_LOCATION_URL:-https://www.bluestarfishguesthouse.com/location/}"
VERIFY_LOCATION_GREP="${VERIFY_LOCATION_GREP:-225 Waverly}"
VERIFY_CONTACT_FORM_REL="${VERIFY_CONTACT_FORM_REL:-wp-content/themes/ocean-breeze/inc-contact-form.php}"
VERIFY_CONTACT_FORM_GREP="${VERIFY_CONTACT_FORM_GREP:-from_name.*Blue Starfish}"
REMOTE_CMD_TIMEOUT="${REMOTE_CMD_TIMEOUT:-45s}"

verify_homepage() {
  echo "=== Verify homepage ${VERIFY_URL} ==="
  local tmp
  tmp="$(mktemp)"
  if ! curl -sL --max-time 30 "${VERIFY_URL}" -o "${tmp}"; then
    rm -f "${tmp}"
    echo "ERROR: could not fetch ${VERIFY_URL}" >&2
    return 1
  fi
  if grep -qF -- "${VERIFY_GREP}" "${tmp}"; then
    rm -f "${tmp}"
    echo "OK: homepage contains \"${VERIFY_GREP}\""
  else
    rm -f "${tmp}"
    echo "ERROR: homepage did not match \"${VERIFY_GREP}\" — hard-refresh or check Reading settings" >&2
    return 1
  fi
}

verify_contact_form_remote() {
  echo "=== Verify contact form on ${REMOTE_HOST} ==="
  local remote_file="${REMOTE_DIR}/${VERIFY_CONTACT_FORM_REL}"
  if timeout "${REMOTE_CMD_TIMEOUT}" ssh \
    -o BatchMode=yes \
    -o ServerAliveInterval=10 \
    -o ServerAliveCountMax=3 \
    "${REMOTE_HOST}" "grep -qE $(printf '%q' "${VERIFY_CONTACT_FORM_GREP}") $(printf '%q' "${remote_file}")"; then
    echo "OK: ${VERIFY_CONTACT_FORM_REL} contains autoreply from_name (Blue Starfish)"
  else
    echo "ERROR: ${VERIFY_CONTACT_FORM_REL} missing expected from_name on server" >&2
    return 1
  fi
}

verify_contact_page() {
  echo "=== Verify contact page ${VERIFY_CONTACT_URL} ==="
  local tmp
  tmp="$(mktemp)"
  if ! curl -sL --max-time 30 "${VERIFY_CONTACT_URL}" -o "${tmp}"; then
    rm -f "${tmp}"
    echo "ERROR: could not fetch ${VERIFY_CONTACT_URL}" >&2
    return 1
  fi
  if grep -qF 'id="contact-form"' "${tmp}"; then
    rm -f "${tmp}"
    echo "OK: contact page renders the contact form"
  else
    rm -f "${tmp}"
    echo "ERROR: contact page missing #contact-form" >&2
    return 1
  fi
}

verify_location_page() {
  echo "=== Verify location page ${VERIFY_LOCATION_URL} ==="
  local tmp
  tmp="$(mktemp)"
  if ! curl -sL --max-time 30 "${VERIFY_LOCATION_URL}" -o "${tmp}"; then
    rm -f "${tmp}"
    echo "ERROR: could not fetch ${VERIFY_LOCATION_URL}" >&2
    return 1
  fi
  if grep -qF -- "${VERIFY_LOCATION_GREP}" "${tmp}"; then
    rm -f "${tmp}"
    echo "OK: location page contains \"${VERIFY_LOCATION_GREP}\""
  else
    rm -f "${tmp}"
    echo "ERROR: location page did not match \"${VERIFY_LOCATION_GREP}\"" >&2
    return 1
  fi
}

verify_deploy() {
  local failed=0
  verify_homepage || failed=1
  verify_contact_form_remote || failed=1
  verify_contact_page || failed=1
  verify_location_page || failed=1
  return "${failed}"
}
