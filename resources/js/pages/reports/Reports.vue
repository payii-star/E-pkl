    <template>
        <div>
        <div class="row g-5 g-xl-8">
            <div class="col-12">
            <div class="card card-xl-stretch mb-5 mb-xl-8">
                <div class="card-header border-0 pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold fs-3 mb-1">Sales Overview</span>
                    <span class="text-muted fw-semibold fs-7">Revenue based on selected date range</span>
                </h3>
                <div class="card-toolbar">
                    <ul class="nav"> 
                    <li class="nav-item">
                        <a
                        class="nav-link btn btn-sm btn-color-muted btn-active btn-active-light-primary"
                        :class="{ active: dateRange === 'week' }"
                        @click="setDateRange('week')"
                        href="#"
                        >
                        Week
                        </a>
                    </li>
                    <li class="nav-item">
                        <a
                        class="nav-link btn btn-sm btn-color-muted btn-active btn-active-light-primary"
                        :class="{ active: dateRange === 'month' }"
                        @click="setDateRange('month')"
                        href="#"
                        >
                        Month
                        </a>
                    </li>
                    <li class="nav-item">
                        <a
                        class="nav-link btn btn-sm btn-color-muted btn-active btn-active-light-primary"
                        :class="{ active: dateRange === 'year' }"
                        @click="setDateRange('year')"
                        href="#"
                        >
                        Year
                        </a>
                    </li>
                    </ul>
                </div>
                </div>
                <div class="card-body">
                <apexchart
                    ref="chart"
                    type="area"
                    :options="chartOptions"
                    :series="chartSeries"
                    height="350"
                ></apexchart>
                </div>
            </div>
            </div>
        </div>
        <div class="row g-5 g-xl-8">
            <div class="col-12">
                <div class="card card-xl-stretch mb-5 mb-xl-8">
                    <div class="card-header border-0">
                        <h3 class="card-title fw-bold text-dark">Sales by Category</h3>
                    </div>
                    <div class="card-body pt-0">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                    <th>Category Name</th>
                                    <th class="text-end">Total Revenue</th>
                                </tr>
                            </thead>
                            <tbody class="fw-semibold text-gray-600">
                                <tr v-for="(category, index) in reports.sales_by_category" :key="index">
                                    <td>{{ category.name }}</td>
                                    <td class="text-end">Rp {{ new Intl.NumberFormat('id-ID').format(category.total_revenue) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </template>
    
    <script setup lang="ts">
    import { ref, onMounted, reactive, computed, watch } from "vue";
    import ApiService from "@/core/services/ApiService";
    import { useThemeStore } from "@/stores/theme";
    import type { ApexOptions } from "apexcharts";
    
    const getCSSVariableValue = (variableName: string) => {
    const anElement = document.documentElement;
    if (typeof getComputedStyle === "function") {
        return getComputedStyle(anElement).getPropertyValue(variableName).trim();
    }
    return "";
    };
    
    // --- Interfaces ---
    // PERBARUI INTERFACE INI
    interface SalesOverTime {
    date: string;
    member_total: number;
    non_member_total: number;
    }
    interface SalesByCategory { name: string; total_revenue: number; }
    interface ReportData {
    sales_over_time: SalesOverTime[];
    sales_by_category: SalesByCategory[];
    }
        
    // --- State ---
    const reports = reactive<ReportData>({
    sales_over_time: [],
    sales_by_category: [],
    });
    const dateRange = ref('week');
    const theme = useThemeStore();
        
    // --- Chart ---
    // PERBARUI CHART SERIES
    const chartSeries = computed(() => {
    return [
        {
        name: "Member",
        data: reports.sales_over_time.map(item => item.member_total),
        },
        {
        name: "Non-Member",
        data: reports.sales_over_time.map(item => item.non_member_total),
        },
    ];
    });
        
    const chartOptions = computed<ApexOptions>(() => {
    const primaryColor = getCSSVariableValue("--bs-primary");
    const dangerColor = getCSSVariableValue("--bs-danger"); // Warna baru untuk non-member
    const lightColor = getCSSVariableValue("--bs-light");
    const gray500 = getCSSVariableValue("--bs-gray-500");
    const gray300 = getCSSVariableValue("--bs-gray-300");
    
    return {
        chart: {
        fontFamily: "inherit",
        type: "area",
        height: 350,
        toolbar: { show: false },
        },
        plotOptions: {},
        legend: { show: true }, // Tampilkan legenda
        dataLabels: { enabled: false },
        fill: {
        type: "gradient",
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.4,
            opacityTo: 0.2,
            stops: [15, 120, 100],
        },
        },
        stroke: {
        curve: "smooth",
        show: true,
        width: 3,
        colors: [primaryColor, dangerColor], // PERBARUI WARNA
        },
        xaxis: {
        categories: reports.sales_over_time.map(item => item.date),
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { style: { colors: gray500, fontSize: "12px" } },
        crosshairs: {
            position: "front",
            stroke: { color: primaryColor, width: 1, dashArray: 3 },
        },
        tooltip: {
            enabled: true,
            formatter: undefined,
            offsetY: 0,
            style: { fontSize: "12px" },
        },
        },
        yaxis: {
        labels: {
            style: { colors: gray500, fontSize: "12px" },
            formatter: function (value) {
            return "Rp " + new Intl.NumberFormat('id-ID').format(value);
            },
        },
        },
        states: {
        normal: { filter: { type: "none", value: 0 } },
        hover: { filter: { type: "none", value: 0 } },
        active: {
            allowMultipleDataPointsSelection: false,
            filter: { type: "none", value: 0 },
        },
        },
        tooltip: {
        style: { fontSize: "12px" },
        y: {
            formatter: function (value) {
            return "Rp " + new Intl.NumberFormat('id-ID').format(value);
            },
        },
        },
        colors: [primaryColor, dangerColor], // PERBARUI WARNA
        grid: {
        borderColor: gray300,
        strokeDashArray: 4,
        yaxis: { lines: { show: true } },
        },
        markers: {
        size: 5,
        colors: [lightColor, lightColor], // Warna isi titik
        strokeColors: [primaryColor, dangerColor], // Warna garis tepi titik
        strokeWidth: 3,
        hover: { size: 7 },
        },
    };
    });
        
    // --- Functions ---
    const fetchReports = () => {
    ApiService.get(`/reports?range=${dateRange.value}`)
        .then(({ data }) => {
        reports.sales_over_time = data.sales_over_time;
        reports.sales_by_category = data.sales_by_category;
        });
    };
    
    const setDateRange = (range: string) => {
    dateRange.value = range;
    };
    
    watch(dateRange, fetchReports);
    
    onMounted(() => {
    fetchReports();
    });
    </script>