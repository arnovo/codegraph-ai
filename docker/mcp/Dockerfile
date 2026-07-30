FROM debian:bookworm-slim

RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates curl \
    && rm -rf /var/lib/apt/lists/*

ARG MCP_VERSION=0.8.1
ARG TARGETARCH

# Download Linux binary from GitHub releases (DeusData/codebase-memory-mcp).
RUN set -eux; \
    case "${TARGETARCH}" in \
      amd64) ARCH="x86_64" ;; \
      arm64) ARCH="aarch64" ;; \
      *) ARCH="x86_64" ;; \
    esac; \
    URL="https://github.com/DeusData/codebase-memory-mcp/releases/download/v${MCP_VERSION}/codebase-memory-mcp-v${MCP_VERSION}-linux-${ARCH}.tar.gz"; \
    curl -fsSL "$URL" -o /tmp/mcp.tar.gz || curl -fsSL "$URL" -o /tmp/mcp.tar.gz; \
    tar -xzf /tmp/mcp.tar.gz -C /usr/local/bin/ 2>/dev/null || true; \
    if [ ! -x /usr/local/bin/codebase-memory-mcp ]; then \
      echo "WARN: MCP binary not found at expected URL; mount host binary or rebuild with MCP_BINARY build-arg" >&2; \
      printf '#!/bin/sh\necho "codebase-memory-mcp placeholder — install Linux binary"\nexit 1\n' > /usr/local/bin/codebase-memory-mcp; \
      chmod +x /usr/local/bin/codebase-memory-mcp; \
    fi; \
    rm -f /tmp/mcp.tar.gz

ENV MCP_DATA_DIR=/data

WORKDIR /data
VOLUME ["/data", "/repos"]

EXPOSE 9749

HEALTHCHECK --interval=10s --timeout=5s --retries=3 \
  CMD curl -sf http://127.0.0.1:9749/ || exit 1

CMD ["codebase-memory-mcp", "--ui=true", "--port=9749"]
