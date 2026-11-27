import sys
def sub_once(path, old, new, count=1):
    b = open(path, 'rb').read()
    o = old.encode('utf-8'); n = new.encode('utf-8')
    c = b.count(o)
    if c != count:
        sys.exit(f"{path}: expected {count} match(es) for {old[:70]!r}, found {c}")
    open(path, 'wb').write(b.replace(o, n))
    print(f"  ok {path}: {old[:60]!r} x{c}")


def sub_eol(path, old, new, count=1):
    """sub_once for files that use CRLF line ends: `old`/`new` are written with \\n and converted to the file's style."""
    b = open(path, 'rb').read()
    crlf = b.count(b'\r\n') > b.count(b'\n') / 2
    if crlf:
        old = old.replace('\r\n', '\n').replace('\n', '\r\n')
        new = new.replace('\r\n', '\n').replace('\n', '\r\n')
    o = old.encode('utf-8'); n = new.encode('utf-8')
    c = b.count(o)
    if c != count:
        sys.exit(f"{path}: expected {count} match(es) for {old[:70]!r}, found {c}")
    open(path, 'wb').write(b.replace(o, n))
    print(f"  ok {path}: {old[:60]!r} x{c} ({'CRLF' if crlf else 'LF'})")
