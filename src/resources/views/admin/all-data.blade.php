<div id="elmenet">
    <template lang="vue">
        <div class="row">
            <div class="col-lg-2">
                <el-card shadow="never">
                    <div class="fs-16">用户</div>
                </el-card>
            </div>
        </div>
    </template>
</div>

<script>
    Dcat.ready(function () {
        new Vue({
            el: '#elmenet',
            data: function () {
                return {visible: false}
            }
        })
    })
</script>
