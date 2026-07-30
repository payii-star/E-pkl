<template>
  <div class="row g-5 g-xl-8">
    <div class="col-xl-6">
      <a href="#" class="card bg-success hoverable card-xl-stretch mb-5 mb-xl-8">
        <div class="card-body">
          <KTIcon icon-name="cheque" icon-class="text-white fs-2x ms-n1" />
          <div class="text-white fw-bold fs-2 mb-2 mt-5">
            {{ stats.todays_transactions }}
          </div>
          <div class="fw-semibold text-white">Transactions Today</div>
        </div>
      </a>
    </div>
    <div class="col-xl-6">
      <a href="#" class="card bg-primary hoverable card-xl-stretch mb-5 mb-xl-8">
        <div class="card-body">
          <KTIcon icon-name="basket" icon-class="text-white fs-2x ms-n1" />
          <div class="text-white fw-bold fs-2 mb-2 mt-5">
            Rp {{ new Intl.NumberFormat('id-ID').format(stats.todays_revenue) }}
          </div>
          <div class="fw-semibold text-white">Revenue Today</div>
        </div>
      </a>
    </div>
  </div>

  <div class="row g-5 g-xl-8">
    <div class="col-xl-6">
      <a href="#" class="card bg-info hoverable card-xl-stretch mb-5 mb-xl-8">
        <div class="card-body">
          <KTIcon icon-name="profile-circle" icon-class="text-white fs-2x ms-n1" />
          <div class="text-white fw-bold fs-2 mb-2 mt-5">
            {{ stats.todays_member_transactions }}
          </div>
          <div class="fw-semibold text-white">Member Transactions Today</div>
        </div>
      </a>
    </div>
    <div class="col-xl-6">
      <a href="#" class="card bg-dark hoverable card-xl-stretch mb-5 mb-xl-8">
        <div class="card-body">
          <KTIcon icon-name="wallet" icon-class="text-white fs-2x ms-n1" />
          <div class="text-white fw-bold fs-2 mb-2 mt-5">
            Rp {{ new Intl.NumberFormat('id-ID').format(stats.todays_member_revenue) }}
          </div>
          <div class="fw-semibold text-white">Member Revenue Today</div>
        </div>
      </a>
    </div>
  </div>

  <div class="row g-5 g-xl-8">
    <div class="col-12">
      <div class="card card-xl-stretch mb-5 mb-xl-8">
        <div class="card-header border-0 pt-5">
          <h3 class="card-title align-items-start flex-column">
            <span class="card-label fw-bold fs-3 mb-1">Sales Last 7 Days</span>
            <span class="text-muted fw-semibold fs-7">Revenue overview</span>
          </h3>
        </div>
        <div class="card-body">
          <apexchart
            ref="chart"
            type="bar"
            :options="chartOptions"
            :series="chartSeries"
            height="350"
          ></apexchart>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-5 g-xl-8">
    <div class="col-xl-6">
      <div class="card card-xl-stretch mb-5 mb-xl-8">
        <div class="card-header border-0">
          <h3 class="card-title fw-bold text-dark">Top 5 Selling Products</h3>
        </div>
        <div class="card-body pt-0">
          <div v-for="product in stats.top_selling_products" :key="product.product_id" class="d-flex align-items-center mb-7">
            <div class="symbol symbol-50px me-5">
              <KTIcon icon-name="box" icon-class="fs-1" />
            </div>
            <div class="d-flex flex-column">
              <a href="#" class="text-dark text-hover-primary fw-bold fs-6">{{ product.product.name }}</a>
              <span class="text-muted fw-semibold fs-7">{{ product.product.sku }}</span>
            </div>
            <div class="d-flex flex-column align-items-end ms-auto">
              <span class="text-dark fw-bold fs-6">{{ product.total_sold }} Sold</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl-6">
      <div class="card card-xl-stretch mb-5 mb-xl-8">
        <div class="card-header border-0">
          <h3 class="card-title fw-bold text-dark">Top 5 Members</h3>
        </div>
        <div class="card-body pt-0">
          <div v-for="member in stats.top_members" :key="member.member_id" class="d-flex align-items-center mb-7">
            <div class="symbol symbol-50px me-5">
              <KTIcon icon-name="user" icon-class="fs-1" />
            </div>
            <div class="d-flex flex-column">
              <a href="#" class="text-dark text-hover-primary fw-bold fs-6">{{ member.member.name }}</a>
              <span class="text-muted fw-semibold fs-7">Total Spent</span>
            </div>
            <div class="d-flex flex-column align-items-end ms-auto">
              <span class="text-dark fw-bold fs-6">Rp {{ new Intl.NumberFormat('id-ID').format(member.total_spent) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive, computed } from "vue";
import ApiService from "@/core/services/ApiService";
import { useThemeStore } from "@/stores/theme";

// --- Definisi Tipe Data ---
interface SalesData {
  date: string;
  member_total: number; // <-- Diubah
  non_member_total: number; // <-- Diubah
}
interface TopProduct {
  product_id: number;
  total_sold: number;
  product: { id: number; name: string; sku: string; };
}
interface TopMember {
  member_id: number;
  total_spent: number;
  member: { id: number; name: string; };
}
interface DashboardStats {
  todays_transactions: number;
  todays_revenue: number;
  todays_member_transactions: number;
  todays_member_revenue: number;
  sales_last_7_days: SalesData[];
  top_selling_products: TopProduct[];
  top_members: TopMember[];
}

// --- State ---
const stats = reactive<DashboardStats>({
  todays_transactions: 0,
  todays_revenue: 0,
  todays_member_transactions: 0,
  todays_member_revenue: 0,
  sales_last_7_days: [],
  top_selling_products: [],
  top_members: [],
});
const theme = useThemeStore();

// --- Chart ---
const chartOptions = computed(() => {
    const labels = stats.sales_last_7_days.map(s => new Date(s.date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }));
    const mode = computed(() => theme.mode);
    const colors = {
      // Tambahkan warna kedua untuk non-member
      memberColor: mode.value === 'dark' ? '#00A3FF' : '#0095E8',
      nonMemberColor: mode.value === 'dark' ? '#546E7A' : '#77838F',
      labelColor: mode.value === 'dark' ? '#A1A5B7' : '#5E6278'
    };
    return {
      chart: { fontFamily: "inherit", type: "bar", toolbar: { show: false } },
      plotOptions: { bar: { horizontal: false, columnWidth: "45%", borderRadius: 5, }, },
      xaxis: { categories: labels, labels: { style: { colors: colors.labelColor, fontSize: "12px" }, }, },
      yaxis: { labels: { style: { colors: colors.labelColor, fontSize: "12px" }, formatter: (value) => `Rp ${new Intl.NumberFormat('id-ID').format(value)}` }, },
      fill: { opacity: 1 },
      colors: [colors.memberColor, colors.nonMemberColor], // <-- Gunakan dua warna
      grid: { borderColor: mode.value === 'dark' ? '#474761' : '#E4E6EF', strokeDashArray: 4, yaxis: { lines: { show: true } } },
      dataLabels: { enabled: false },
      legend: { show: true } // Tampilkan legenda
    };
});

const chartSeries = computed(() => {
  return [
    {
      name: "Member",
      data: stats.sales_last_7_days.map(s => s.member_total),
    },
    {
      name: "Non-Member",
      data: stats.sales_last_7_days.map(s => s.non_member_total),
    },
  ];
});

// --- API Fetch ---
const fetchDashboardStats = () => {
  ApiService.get("/dashboard-stats")
    .then(({ data }) => {
      stats.todays_transactions = data.todays_transactions;
      stats.todays_revenue = data.todays_revenue;
      stats.todays_member_transactions = data.todays_member_transactions;
      stats.todays_member_revenue = data.todays_member_revenue;
      stats.sales_last_7_days = data.sales_last_7_days;
      stats.top_selling_products = data.top_selling_products;
      stats.top_members = data.top_members;
    })
    .catch(({ response }) => {
      console.error("Error fetching dashboard stats:", response);
    });
};

onMounted(() => {
  fetchDashboardStats();
});
</script>