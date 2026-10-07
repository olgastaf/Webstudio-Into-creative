(function () {
    'use strict';

    let map = null;
    let polygon = null;

    ymaps.ready(init);

    function init() {
        const mapElement = document.getElementById('delivery-zone-map');

        if (!mapElement) {
            return;
        }

        map = new ymaps.Map('delivery-zone-map', {
            center: [55.397, 36.615],
            zoom: 11,
            controls: [
                'zoomControl',
                'searchControl',
                'typeSelector',
                'fullscreenControl'
            ]
        });

        bindButtons();
        loadExistingPolygon();
    }

    function bindButtons() {
        const drawButton = document.getElementById('draw-zone');
        const editButton = document.getElementById('edit-zone');
        const clearButton = document.getElementById('clear-zone');

        if (drawButton) {
            drawButton.addEventListener('click', drawNewPolygon);
        }

        if (editButton) {
            editButton.addEventListener('click', editExistingPolygon);
        }

        if (clearButton) {
            clearButton.addEventListener('click', clearPolygon);
        }
    }

    function drawNewPolygon() {
        if (!map) {
            alert('Карта еще не загрузилась');
            return;
        }

        removeCurrentPolygon();

        polygon = new ymaps.GeoObject({
            geometry: {
                type: 'Polygon',
                coordinates: [[]]
            },
            properties: {
                hintContent: 'Новая зона доставки'
            }
        }, {
            fillColor: '#ed4543',
            fillOpacity: 0.35,
            strokeColor: '#ed4543',
            strokeWidth: 3,
            strokeOpacity: 0.9,
            editorDrawingCursor: 'crosshair',
            editorMaxPoints: 1000
        });

        map.geoObjects.add(polygon);

        polygon.geometry.events.add(
            'change',
            savePolygonToTextarea
        );

        polygon.editor.startDrawing();
    }

    function editExistingPolygon() {
        if (!polygon) {
            alert('Сначала загрузите или нарисуйте полигон');
            return;
        }

        polygon.editor.startEditing();
    }

    function clearPolygon() {
        removeCurrentPolygon();

        const textarea = document.getElementById('UF_GEOJSON');

        if (textarea) {
            textarea.value = '';
        }
    }

    function removeCurrentPolygon() {
        if (polygon && map) {
            polygon.editor.stopEditing();
            map.geoObjects.remove(polygon);
            polygon = null;
        }
    }

    function loadExistingPolygon() {
        const textarea = document.getElementById('UF_GEOJSON');

        if (!textarea || !textarea.value.trim()) {
            return;
        }

        try {
            const geoJson = JSON.parse(textarea.value);

            if (
                !geoJson ||
                geoJson.type !== 'Polygon' ||
                !geoJson.coordinates
            ) {
                throw new Error('Ожидался GeoJSON типа Polygon');
            }

            const yandexCoordinates =
                geoJsonToYandexCoordinates(geoJson.coordinates);

            polygon = new ymaps.GeoObject({
                geometry: {
                    type: 'Polygon',
                    coordinates: yandexCoordinates
                },
                properties: {
                    hintContent: 'Зона доставки'
                }
            }, {
                fillColor: '#ed4543',
                fillOpacity: 0.35,
                strokeColor: '#ed4543',
                strokeWidth: 3,
                strokeOpacity: 0.9,
                editorDrawingCursor: 'crosshair',
                editorMaxPoints: 1000
            });

            map.geoObjects.add(polygon);

            polygon.geometry.events.add(
                'change',
                savePolygonToTextarea
            );

            map.setBounds(
                polygon.geometry.getBounds(),
                {
                    checkZoomRange: true,
                    duration: 300
                }
            );
        } catch (error) {
            alert('Ошибка загрузки полигона: ' + error.message);
        }
    }

    function savePolygonToTextarea() {
        if (!polygon) {
            return;
        }

        const textarea = document.getElementById('UF_GEOJSON');

        if (!textarea) {
            return;
        }

        const yandexCoordinates =
            polygon.geometry.getCoordinates();

        const geoJson = {
            type: 'Polygon',
            coordinates: yandexToGeoJsonCoordinates(
                yandexCoordinates
            )
        };

        textarea.value = JSON.stringify(
            geoJson,
            null,
            2
        );
    }

    function geoJsonToYandexCoordinates(coordinates) {
        return coordinates.map(function (ring) {
            return ring.map(function (point) {
                return [
                    point[1],
                    point[0]
                ];
            });
        });
    }

    function yandexToGeoJsonCoordinates(coordinates) {
        return coordinates.map(function (ring) {
            return ring.map(function (point) {
                return [
                    point[1],
                    point[0]
                ];
            });
        });
    }
})();