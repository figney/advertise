<div class="row" id="sync-title">
    <template>
        <div class="col-lg-2">
            <div class="card">
                <div class="info-box bg-transparent" style="margin-bottom: 0;">
                    <span class="info-box-icon"><i class="fa  fa-clock-o"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">
                            <span class="mr-1">服务器时间</span>
                            <el-button type="text" size="mini" disabled icon="el-icon-refresh-right">刷新</el-button></span>
                        <span class="fs-20">@{{time|localTime}}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2">
            <div class="card">
                <div class="info-box bg-transparent" style="margin-bottom: 0;">
                    <span class="info-box-icon"><i class="fa fa-desktop"></i></span>
                    <div class="info-box-content">
                    <span class="info-box-text">
                        <span class="mr-1">在线设备</span>
                        <el-button type="text" size="mini" @click="getOnlineNum" :loading="reload" icon="el-icon-refresh-right">刷新</el-button>
                    </span>
                        <span class="fs-20" v-html="allUserNum"></span>

                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="card">
                <div class="info-box bg-transparent" style="margin-bottom: 0;">
                    <span class="info-box-icon"><i class="fa fa-sign-in"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">
                            <span class="mr-1">今日签到</span>
                            <el-button type="text" size="mini" @click="getSignData" :loading="signData_r" icon="el-icon-refresh-right">刷新</el-button>
                        </span>
                        <div class="fs-12 flex align-center justify-between" v-if="signData">
                            <div>
                                <span class="fs-20"> @{{signData.today_count}}</span>
                            </div>
                            <div>
                                <span><i class="fa fa-circle text-primary"></i> 昨日此时:</span>
                                <span>@{{signData.yesterday_time_count}}</span>
                            </div>
                            <div>
                                <span><i class="fa fa-circle text-warning"></i> 昨日:</span>
                                <span>@{{signData.yesterday_count}}</span>
                            </div>
                        </div>
                        <div class="fs-20" v-else>-</div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
<script>
    Dcat.ready(function () {
        new Vue({
            el: '#sync-title',
            data() {
                return {
                    allUserNum: 0,
                    signData: null,
                    signData_r: false,
                    reload: false,
                    clockInterval: null,
                    time: "-"
                }
            },
            mounted() {
                this.getOnlineNum()
                this.getSignData()
                this.startClock('{{now()->format("Y-m-d H:i:s")}}')
            },
            beforeDestroy() {
                window.clearInterval(this.clockInterval)

            },
            methods: {
                startClock(time) {
                    window.clearInterval(this.clockInterval)
                    this.time = time
                    this.clockInterval = setInterval(() => {
                        this.time = new Date(new Date(this.time).getTime() + 1000)
                    }, 1000)
                },
                getOnlineNum() {
                    this.reload = true;
                    axios.get("{{admin_route('admin.getOnlineNum')}}").then((data) => {
                        this.allUserNum = data.data;
                        this.reload = false;
                    })
                },
                getSignData() {
                    this.signData_r = true
                    axios.get("{{admin_route('admin.getSignData')}}").then((data) => {
                        this.signData = data.data;
                        this.signData_r = false
                    })
                }
            },
            filters: {
                localTime(time) {
                    try {
                        let str = 'HH:MM:SS'
                        let t = new Date(time)
                        let yyyy = t.getFullYear()
                        let mm = (t.getMonth() + 1) > 9 ? (t.getMonth() + 1) : '0' + (t.getMonth() + 1)
                        let dd = t.getDate() > 9 ? t.getDate() : '0' + t.getDate()
                        let HH = t.getHours() > 9 ? t.getHours() : '0' + t.getHours()
                        let MM = t.getMinutes() > 9 ? t.getMinutes() : '0' + t.getMinutes()
                        let SS = t.getSeconds() > 9 ? t.getSeconds() : '0' + t.getSeconds()
                        if (isNaN(yyyy)) {
                            return time
                        }
                        return str.replace('yyyy', yyyy).replace('mm', mm).replace('dd', dd).replace('HH', HH).replace('MM', MM).replace('SS', SS)
                    } catch (error) {
                        return time
                    }
                }
            }
        })
    })
</script>


