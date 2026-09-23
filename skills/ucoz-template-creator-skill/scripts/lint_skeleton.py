#!/usr/bin/env python3
"""Local preflight checks for a uCoz tmaker framework."""

from __future__ import annotations

import argparse
import json
import re
import sys
from pathlib import Path


BLOCK_RE = re.compile(r"<!--\s*<(?P<close>/)?(?P<name>[a-z][a-z0-9_]*?)>\s*-->", re.IGNORECASE)
INFORMER_RE = re.compile(r"<!--\s*<new_informer>\s*-->(.*?)<!--\s*</new_informer>\s*-->", re.IGNORECASE | re.DOTALL)
CONFIG_RE = re.compile(r"<!--\s*<config>\s*-->(.*?)<!--\s*</config>\s*-->", re.IGNORECASE | re.DOTALL)
POPUP_RE = re.compile(r"<!--\s*<popup>\s*-->(.*?)<!--\s*</popup>\s*-->", re.IGNORECASE | re.DOTALL)
NAMED_BLOCK_RE = re.compile(
    r"<!--\s*<(?P<name>[a-z][a-z0-9_]*?)>\s*-->(?P<body>.*?)<!--\s*</(?P=name)>\s*-->",
    re.IGNORECASE | re.DOTALL,
)
REQUIRED_INFORMER_KEYS = {
    "title", "module", "sort", "max_entries", "max_columns", "categories_list",
    "entries_list", "title_max_length", "data_type", "curdate", "template", "no_entries_msg",
}
MOJIBAKE_RE = re.compile(
    r"(?:Р[°±Ііґµ¶·ё№є»јЅѕї]|С[Ђѓ‚„…†‡€‰Љ‹ЊЌЋЏ]|в(?:Ђ|†))"
)


def add(items: list[dict[str, str]], code: str, message: str) -> None:
    items.append({"code": code, "message": message})


def extract_blocks(text: str) -> tuple[dict[str, int], dict[str, int]]:
    starts: dict[str, int] = {}
    ends: dict[str, int] = {}
    for match in BLOCK_RE.finditer(text):
        name = match.group("name").lower()
        target = ends if match.group("close") else starts
        target[name] = target.get(name, 0) + 1
    return starts, ends


def lint(text: str) -> dict[str, object]:
    errors: list[dict[str, str]] = []
    warnings: list[dict[str, str]] = []
    starts, ends = extract_blocks(text)

    if MOJIBAKE_RE.search(text):
        add(errors, "MOJIBAKE", "Framework contains text that looks like UTF-8 decoded as a legacy Windows code page. Read and transmit the source explicitly as UTF-8 before validation or publication.")

    if "$POWERED_BY$" not in text:
        add(errors, "MISSING_POWERED_BY", "Framework must contain $POWERED_BY$.")

    for name in ("header", "middle", "footer", "popup", "config"):
        if starts.get(name, 0) != 1 or ends.get(name, 0) != 1:
            add(errors, "BLOCK_PAIR", f"Expected one opening and one closing {name} block; found {starts.get(name, 0)}/{ends.get(name, 0)}.")

    for name in sorted(set(starts) | set(ends)):
        if name == "new_informer":
            continue
        if starts.get(name, 0) != ends.get(name, 0):
            add(errors, "UNBALANCED_BLOCK", f"Service block {name} has {starts.get(name, 0)} openings and {ends.get(name, 0)} closings.")

    popup = POPUP_RE.search(text)
    if popup and ("[TITLE]" not in popup.group(1) or "[BODY]" not in popup.group(1)):
        add(errors, "POPUP_PLACEHOLDERS", "Popup must preserve [TITLE] and [BODY].")

    for block in NAMED_BLOCK_RE.finditer(text):
        name = block.group("name").lower()
        if name in {"container1", "container2"} and "[BODY]" in block.group("body"):
            add(errors, "DUPLICATE_BODY", f"{name} must not contain [BODY]; keep the page body only in middle.")

    if re.search(r"<style\b", text, re.IGNORECASE):
        add(errors, "INLINE_CSS", "Do not put <style> rules in the framework; use template 3/3.")

    if not re.search(r"<link\b[^>]*href=[\"'][^\"']*/_st/my\.css\?v=[^\"']+[\"'][^>]*>", text, re.IGNORECASE):
        add(errors, "CSS_LINK", "Add a versioned /_st/my.css?v=... stylesheet link in <head>.")

    if re.search(
        r"<link\b[^>]*href=[\"'](?:/css/)?/?my\.css(?:\?[^\"']*)?[\"']",
        text,
        re.IGNORECASE,
    ):
        add(
            errors,
            "BARE_MY_CSS",
            "Bare /my.css (or /css/my.css) is forbidden; use versioned /_st/my.css?v=... so compiled 3/3 styles apply.",
        )

    header_match = re.search(
        r"<!--\s*<header>\s*-->(.*?)<!--\s*</header>\s*-->",
        text,
        re.IGNORECASE | re.DOTALL,
    )
    header_body = header_match.group(1) if header_match else ""
    has_menu_placeholder = bool(re.search(r"<!--\s*<sblock_nmenu>\s*-->", text, re.IGNORECASE))
    has_nmenu = bool(re.search(r"\$NMENU_\d+\$", text))
    has_smenu = bool(re.search(r"\$SMENU_\d+\$", text))
    if has_menu_placeholder and not (has_nmenu or has_smenu):
        add(
            warnings,
            "MENU_VARIABLE_MISSING",
            "sblock_nmenu is declared but neither $NMENU_N$ nor $SMENU_N$ appears; expand the placeholder or reuse a menu variable.",
        )
    if header_body and not (re.search(r"\$NMENU_\d+\$", header_body) or re.search(r"\$SMENU_\d+\$", header_body) or re.search(r"sblock_nmenu", header_body, re.IGNORECASE)):
        if has_nmenu or has_smenu or has_menu_placeholder:
            add(
                warnings,
                "MENU_NOT_IN_HEADER",
                "A menu variable/placeholder exists outside header; confirm the primary nav lives in the header path ($NMENU_*$ vertical, $SMENU_*$ horizontal).",
            )

    for match in BLOCK_RE.finditer(text):
        name = match.group("name").lower()
        if name.startswith("global_") and not re.fullmatch(r"global_[a-z]{1,10}", name):
            add(errors, "GLOBAL_NAME", f"Invalid global block name {name}; use global_ plus 1–10 lowercase Latin letters.")

    for block in NAMED_BLOCK_RE.finditer(text):
        name = block.group("name").lower()
        if name.startswith("global_"):
            code = f"$GLOBAL_{name[7:].upper()}$"
            if code in text:
                add(warnings, "GLOBAL_DOUBLE_RENDER", f"{name} is rendered at its declaration position and {code} is also called explicitly; remove the second render or move the declaration to the intended location.")

    if re.search(r"\{IF\}", text, re.IGNORECASE):
        add(errors, "LEGACY_IF", "Legacy {IF} syntax is forbidden.")

    nmenu_placeholders = len(re.findall(r"<!--\s*<sblock_nmenu>\s*-->", text, re.IGNORECASE))
    if nmenu_placeholders > 1:
        add(warnings, "REPEATED_NMENU_PLACEHOLDER", "Declare sblock_nmenu once; repeated CONTENT placeholders may render literally. Reuse the generated $NMENU_1$ code in footer and containers.")

    condition_open = len(re.findall(r"<\?if(?:not)?\s*\(", text, re.IGNORECASE))
    condition_close = len(re.findall(r"<\?endif\?>", text, re.IGNORECASE))
    if condition_open != condition_close:
        add(errors, "UNBALANCED_CONDITION", f"Found {condition_open} condition openings and {condition_close} endings.")
    if re.search(r"<\?elseif\b", text, re.IGNORECASE):
        add(errors, "UNSUPPORTED_ELSEIF", "uCoz does not support <?elseif?>; nest conditions.")

    informer_count = 0
    for index, match in enumerate(INFORMER_RE.finditer(text), start=1):
        informer_count += 1
        try:
            data = json.loads(match.group(1).strip())
        except json.JSONDecodeError as exc:
            add(errors, "INFORMER_JSON", f"Informer {index} has invalid JSON: {exc.msg}.")
            continue
        missing = sorted(REQUIRED_INFORMER_KEYS - set(data))
        if missing:
            add(errors, "INFORMER_KEYS", f"Informer {index} misses: {', '.join(missing)}.")
        if not isinstance(data.get("template", ""), str) or not data.get("template"):
            add(errors, "INFORMER_TEMPLATE", f"Informer {index} needs a non-empty template string.")

    if starts.get("new_informer", 0) != informer_count or ends.get("new_informer", 0) != informer_count:
        add(errors, "INFORMER_MARKERS", "One or more new_informer marker pairs could not be parsed.")

    config = CONFIG_RE.search(text)
    config_data: dict[str, object] = {}
    if config:
        try:
            config_data = json.loads(config.group(1).strip())
        except json.JSONDecodeError as exc:
            add(errors, "CONFIG_JSON", f"Config has invalid JSON: {exc.msg}.")
        else:
            for key in config_data:
                if not re.fullmatch(r"[a-z_]+", key):
                    add(errors, "CONFIG_KEY", f"Invalid config key {key!r}; use lowercase a-z and underscore.")
            for size_key in ("rstars_b_size", "rstars_s_size"):
                if size_key in config_data:
                    try:
                        if int(config_data[size_key]) <= 0:
                            raise ValueError
                    except (TypeError, ValueError):
                        add(errors, "RATING_SIZE", f"{size_key} must be a positive integer.")

    has_rstars = bool(re.search(r"<\?\$RSTARS\$\(", text, re.IGNORECASE))
    if "$RATING$" in text or "$RATE_FORM$" in text or has_rstars:
        rating_images = [str(config_data.get(key, "")).strip() for key in ("rstars_b_image", "rstars_s_image")]
        if not all(rating_images):
            add(warnings, "RATING_DEFAULT_SPRITE", "Rating is used but one or both custom sprite URLs are empty; confirm the default sprite is intentional.")
        if re.search(r"(?:★|☆){3,}", text) or len(re.findall(r"<use\b[^>]*(?:star|rating)", text, re.IGNORECASE)) >= 5:
            add(errors, "STATIC_RATING", "Do not replace the uCoz rating generator with a row of static stars.")

    metadata_vars = re.compile(
        r"\$(?:DATE|TIME|READS|LOADS|REDIRECTS|REVIEWS|COMMENTS_NUM|USERNAME|AUTHOR_NAME|DURATION|VI_READS)\$"
    )
    for block in NAMED_BLOCK_RE.finditer(text):
        name = block.group("name").lower()
        body = block.group("body")
        if (name.endswith("_entry_view") or name.endswith("_entry_page__body")) and metadata_vars.search(body):
            if "<svg" not in body:
                add(warnings, "MATERIAL_ICONS", f"{name} renders metadata but has no inline SVG icons.")
        if "$LOADS$" in body and not re.search(r"download|load", body, re.IGNORECASE):
            add(warnings, "DOWNLOAD_ICON", f"{name} renders $LOADS$ without an identifiable download icon/class.")
        if "$REDIRECTS$" in body and not re.search(r"external|redirect|link", body, re.IGNORECASE):
            add(warnings, "REDIRECT_ICON", f"{name} renders $REDIRECTS$ without an identifiable external-link icon/class.")
        if name.endswith("_entry_page__body"):
            block_has_rstars = bool(re.search(r"<\?\$RSTARS\$\(", body, re.IGNORECASE))
            if "$RATING$" in body and not block_has_rstars:
                add(errors, "NUMERIC_RATING_FORM", f"{name} renders numeric $RATING$ without the verified <?$RSTARS$(...)?> generator.")
            if block_has_rstars:
                rating_images = [str(config_data.get(key, "")).strip() for key in ("rstars_b_image", "rstars_s_image")]
                if not all(rating_images):
                    add(errors, "RATING_SPRITE_REQUIRED", f"{name} uses <?$RSTARS$(...)?> but config does not provide both custom rating sprites.")

    if len(text) < 1500:
        add(warnings, "SHORT", "Framework is unusually short; server may return SKELETON_TOO_SHORT.")
    if not has_rstars and not any(code in text for code in ("$RATE_FORM$", "$RATING$", "$ENTRY_RATING$", "$COMMENT_RATING$")):
        add(warnings, "NO_RATING", "No material, guestbook, or comment rating generator found; confirm rating is intentionally omitted.")
    if "$SEO_TITLE$" not in text:
        add(warnings, "NO_SEO_TITLE", "Framework head has no verified $SEO_TITLE$ path; confirm generated full-page templates receive SEO-aware titles.")
    if "$SEO_DESCRIPTION$" not in text:
        add(warnings, "NO_SEO_DESCRIPTION", "Framework head has no verified conditional $SEO_DESCRIPTION$ meta tag.")
    if re.search(r"/\.s/t/\d+/", text):
        add(warnings, "STANDARD_DESIGN_ASSET", "Framework still references a numbered standard-design asset path; verify it is intentional and not stale.")
    if informer_count:
        add(warnings, "INFORMER_PASS_ONE", "After creation, remove new_informer blocks, place returned $MYINF_N$ codes, and publish pass two.")

    return {
        "valid": not errors,
        "errors": errors,
        "warnings": warnings,
        "stats": {
            "characters": len(text),
            "service_blocks": sum(starts.values()),
            "informers": informer_count,
            "conditions": condition_open,
        },
    }


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("framework", type=Path)
    parser.add_argument("--json", action="store_true", help="Print machine-readable JSON.")
    args = parser.parse_args()
    try:
        text = args.framework.read_text(encoding="utf-8-sig")
    except OSError as exc:
        print(f"ERROR: {exc}", file=sys.stderr)
        return 2
    result = lint(text)
    if args.json:
        print(json.dumps(result, ensure_ascii=False, indent=2))
    else:
        print("VALID" if result["valid"] else "INVALID")
        for kind in ("errors", "warnings"):
            for item in result[kind]:
                print(f"{kind[:-1].upper()} [{item['code']}]: {item['message']}")
        stats = result["stats"]
        print(f"characters={stats['characters']} blocks={stats['service_blocks']} informers={stats['informers']} conditions={stats['conditions']}")
    return 0 if result["valid"] else 1


if __name__ == "__main__":
    raise SystemExit(main())
