#!/usr/bin/env bash

# Upsert KEY=VALUE in a dotenv file (portable macOS/Linux).
upsert_env() {
  local key="$1"
  local value="$2"
  local file="${3:-.env}"

  if [[ ! -f "$file" ]]; then
    touch "$file"
  fi

  local tmp
  tmp="$(mktemp)"
  local found=0

  while IFS= read -r line || [[ -n "$line" ]]; do
    if [[ "$line" =~ ^${key}= ]]; then
      printf '%s=%s\n' "$key" "$value" >>"$tmp"
      found=1
    else
      printf '%s\n' "$line" >>"$tmp"
    fi
  done <"$file"

  if [[ "$found" -eq 0 ]]; then
    printf '%s=%s\n' "$key" "$value" >>"$tmp"
  fi

  mv "$tmp" "$file"
}

env_is_empty() {
  local key="$1"
  local file="${2:-.env}"

  if [[ ! -f "$file" ]]; then
    return 0
  fi

  local value
  value="$(grep -E "^${key}=" "$file" 2>/dev/null | head -n1 | cut -d= -f2- | tr -d '"' | tr -d "'")"
  [[ -z "$value" ]]
}
