<div class="padding-bottom-sm">
    <div id="g2-user"></div>
</div>
<script>
    Dcat.ready(function () {
        const {Area} = G2Plot;
        const data = @JSON($data);
        const line = new Area('g2-user', {
            data,
            xField: 'year',
            yField: 'value',
            seriesField: 'category',
            height: 150,
            smooth: true,
            animation: {
                appear: {
                    animation: 'path-in',
                    duration: 1000,
                },
            },
            /*yAxis: {
                label: null,
                grid: {
                    line: null
                }
            }*/
        });


        line.render();
    })
</script>
