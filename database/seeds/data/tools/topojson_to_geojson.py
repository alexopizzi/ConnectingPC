"""
Confini dei comuni della provincia di Piacenza per la mappa pubblica (vault "61").

Fonte: openpolis/geojson-italy, topojson/limits_P_33_municipalities.topo.json (ISTAT, confini al
1° gennaio 2026, semplificati al 20% con topologia condivisa), licenza CC-BY 4.0.
Script usato una tantum (non fa parte dell'applicazione): decodifica il TopoJSON, arrotonda le
coordinate a 5 decimali (~1 m) e scrive un GeoJSON con le sole proprietà necessarie.

Uso:  python -I topojson_to_geojson.py <input.topo.json> <output.json>
"""
import json
import sys


def main(src, dst):
    topo = json.load(open(src, encoding='utf-8'))
    scale = topo['transform']['scale']
    translate = topo['transform']['translate']
    arcs = []
    for arc in topo['arcs']:
        x = y = 0
        points = []
        for dx, dy in arc:
            x += dx
            y += dy
            points.append([round(x * scale[0] + translate[0], 5), round(y * scale[1] + translate[1], 5)])
        arcs.append(points)

    def ring(indexes):
        coords = []
        for i in indexes:
            points = arcs[i] if i >= 0 else list(reversed(arcs[~i]))
            coords.extend(points if not coords else points[1:])
        return coords

    layer = next(iter(topo['objects'].values()))
    features = []
    for geom in layer['geometries']:
        if geom['type'] == 'Polygon':
            geometry = {'type': 'Polygon', 'coordinates': [ring(r) for r in geom['arcs']]}
        else:
            geometry = {'type': 'MultiPolygon', 'coordinates': [[ring(r) for r in poly] for poly in geom['arcs']]}
        props = geom['properties']
        features.append({'type': 'Feature', 'properties': {'istat': props['com_istat_code'], 'name': props['name']}, 'geometry': geometry})
    features.sort(key=lambda f: f['properties']['istat'])
    out = {
        'type': 'FeatureCollection',
        'attribution': 'Confini comunali ISTAT 2026 (CC-BY 4.0), elaborazione openpolis/geojson-italy',
        'features': features,
    }
    with open(dst, 'w', encoding='utf-8', newline='\n') as fh:
        json.dump(out, fh, ensure_ascii=False, separators=(',', ':'))
    print(len(features), 'comuni')


if __name__ == '__main__':
    main(sys.argv[1], sys.argv[2])
