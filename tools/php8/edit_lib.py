import sys
def sub_once(path, old, new, count=1):
    b = open(path, 'rb').read()
    o = old.encode('utf-8'); n = new.encode('utf-8')
    c = b.count(o)
    if c != count:
        sys.exit(f"{path}: expected {count} match(es) for {old[:70]!r}, found {c}")
    open(path, 'wb').write(b.replace(o, n))
    print(f"  ok {path}: {old[:60]!r} x{c}")
