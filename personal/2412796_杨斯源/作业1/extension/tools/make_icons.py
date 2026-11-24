# -*- coding: utf-8 -*-
"""生成插件图标（16/48/128 px）。

纯标准库实现：手写 PNG 编码 + 4 倍超采样抗锯齿，不需要 Pillow。
    E:\\python\\python.exe make_icons.py

图形：品牌蓝圆角方块 + 白色表格网格（呼应「表格 → SQL」）。
"""

import os
import struct
import zlib

OUT_DIR = os.path.normpath(os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "icons"))
SIZES = (16, 48, 128)
SS = 4  # 超采样倍数

BG = (43, 108, 176)      # #2b6cb0
FG = (255, 255, 255)


def rounded_alpha(x, y, size, radius, soft):
    """圆角矩形的覆盖率（0~1），soft 为边缘柔化宽度。"""
    cx = min(max(x, radius), size - radius)
    cy = min(max(y, radius), size - radius)
    dx = x - cx
    dy = y - cy
    dist = (dx * dx + dy * dy) ** 0.5
    if dist <= radius - soft:
        return 1.0
    if dist >= radius:
        return 0.0
    return (radius - dist) / soft


def in_grid(x, y, size):
    """表格网格：外框 + 一条竖分隔 + 一条横分隔。"""
    x0, x1 = 0.235 * size, 0.765 * size
    y0, y1 = 0.275 * size, 0.725 * size
    t = 0.085 * size
    if x0 <= x <= x1 and y0 <= y <= y1:
        on_border = (x - x0 <= t) or (x1 - x <= t) or (y - y0 <= t) or (y1 - y <= t)
        on_v = abs(x - 0.5 * size) <= t / 2
        on_h = abs(y - 0.5 * size) <= t / 2
        return on_border or on_v or on_h
    return False


def render(size):
    """返回 size x size 的 RGBA 像素（每行一个 bytes）。"""
    n = size * SS
    hi = []
    for j in range(n):
        row = []
        for i in range(n):
            x, y = (i + 0.5) / SS, (j + 0.5) / SS
            a = rounded_alpha(x, y, size, 0.22 * size, max(1.0, 0.06 * size))
            if a <= 0.0:
                row.append((0, 0, 0, 0))
            elif in_grid(x, y, size):
                row.append((FG[0], FG[1], FG[2], 255))
            else:
                row.append((BG[0], BG[1], BG[2], 255 - int((1 - a) * 255)))
        hi.append(row)

    # 盒式降采样
    out = []
    for j in range(size):
        row = bytearray()
        for i in range(size):
            r = g = b = a = 0
            for dj in range(SS):
                for di in range(SS):
                    pr, pg, pb, pa = hi[j * SS + dj][i * SS + di]
                    w = pa / 255.0
                    r += pr * w
                    g += pg * w
                    b += pb * w
                    a += pa
            total_w = sum(hi[j * SS + dj][i * SS + di][3] / 255.0
                          for dj in range(SS) for di in range(SS))
            alpha = int(a / (SS * SS))
            if total_w > 0:
                row += bytes((int(r / total_w), int(g / total_w), int(b / total_w), alpha))
            else:
                row += bytes((0, 0, 0, 0))
        out.append(bytes(row))
    return out


def write_png(path, size, rows):
    raw = b"".join(b"\x00" + r for r in rows)  # 每行前置 filter type 0

    def chunk(tag, data):
        body = tag + data
        return struct.pack(">I", len(data)) + body + struct.pack(">I", zlib.crc32(body) & 0xFFFFFFFF)

    ihdr = struct.pack(">IIBBBBB", size, size, 8, 6, 0, 0, 0)  # 8bit RGBA
    blob = (b"\x89PNG\r\n\x1a\n"
            + chunk(b"IHDR", ihdr)
            + chunk(b"IDAT", zlib.compress(raw, 9))
            + chunk(b"IEND", b""))
    with open(path, "wb") as f:
        f.write(blob)
    return len(blob)


def main():
    os.makedirs(OUT_DIR, exist_ok=True)
    for size in SIZES:
        path = os.path.join(OUT_DIR, "icon{}.png".format(size))
        n = write_png(path, size, render(size))
        print("{}  ({}x{}, {} 字节)".format(path, size, size, n))


if __name__ == "__main__":
    main()
