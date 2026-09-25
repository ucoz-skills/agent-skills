# Security Policy

## Scope

This repository contains official uCoz Agent Skills and IDE plugin packaging.
Runtime access to sites goes through the official remote MCP at
`https://www.ucoz.com/mcp` (Control Panel authorization).

## How credentials are handled

- Client MCP configs in this repository contain only the public MCP URL.
- Do not commit API tokens, passwords, cookies, or Control Panel session data.
- No credentials from this repository are sent to third-party services.

## Reporting

Report security issues via [uCoz support](https://www.ucoz.com/). Do not open a
public issue that includes secret values.
