<template>
	<!--begin::Authentication Layout -->
	<div class="auth-page">
		<div
			class="auth-page__bg"
			:style="setting?.bg_auth ? `--auth-bg: url('${setting.bg_auth}')` : ''"
		></div>
		<div class="auth-page__overlay"></div>

		<div class="auth-scroll">
			<div class="auth-center">
				<!--begin::Brand-->
				<router-link to="/" class="auth-brand">
					<img
						v-if="setting?.logo"
						:src="setting.logo"
						:alt="setting?.app"
						class="auth-brand__logo"
					/>
					<span v-else class="auth-brand__mark">e</span>
					<span class="auth-brand__text">{{ setting?.app || "PKL" }}</span>
				</router-link>
				<!--end::Brand-->

				<!--begin::Headline-->
				<h1 class="auth-headline">Satu ruang untuk seluruh perjalanan PKL-mu</h1>
				<!--end::Headline-->

				<!--begin::Form card-->
				<div class="auth-card">
					<router-view></router-view>
				</div>
				<!--end::Form card-->
			</div>
		</div>
	</div>
	<!--end::Authentication Layout -->
</template>

<script lang="ts">
import { getAssetPath } from "@/core/helpers/assets";
import { defineComponent, onMounted } from "vue";
import LayoutService from "@/core/services/LayoutService";
import { useBodyStore } from "@/stores/body";
import { useSetting } from "@/services";

export default defineComponent({
	name: "auth-layout",
	components: {},
	setup() {
		const store = useBodyStore();
		const { data: setting = {} } = useSetting();

		onMounted(() => {
			LayoutService.emptyElementClassesAndAttributes(document.body);

			store.addBodyClassname("app-blank");
			store.addBodyClassname("bg-body");
		});

		return {
			getAssetPath,
			setting,
		};
	},
});
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Manrope:wght@400;500;600;700;800&display=swap");

.auth-page {
	--auth-bg: none;
	position: relative;
	height: 100vh;
	font-family: "Manrope", sans-serif;
}

/* Fixed background layer — stays put while content scrolls */
.auth-page__bg {
	position: fixed;
	inset: 0;
	background-color: #0b1a33;
	background-image: var(--auth-bg);
	background-size: cover;
	background-position: center;
	z-index: 0;
}
.auth-page__overlay {
	position: fixed;
	inset: 0;
	z-index: 0;
	background:
		radial-gradient(circle at 18% 12%, rgba(59, 130, 246, 0.22), transparent 42%),
		radial-gradient(circle at 82% 88%, rgba(37, 99, 235, 0.2), transparent 46%),
		linear-gradient(180deg, rgba(11, 26, 51, 0.93), rgba(11, 26, 51, 0.93));
}

/* Scrollable layer — only this part moves when scrolling */
.auth-scroll {
	position: relative;
	z-index: 1;
	height: 100%;
	overflow-y: auto;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 64px 24px;
}

.auth-center {
	width: 100%;
	max-width: 420px;
	display: flex;
	flex-direction: column;
	align-items: center;
	text-align: center;
	color: #eef3fb;
}

/* Brand */
.auth-brand {
	display: inline-flex;
	align-items: center;
	gap: 8px;
	font-family: "Fraunces", serif;
	font-weight: 600;
	font-size: 18px;
	margin-bottom: 40px;
	color: inherit;
	text-decoration: none;
}
.auth-brand__logo {
	width: 30px;
	height: 30px;
	object-fit: contain;
	border-radius: 8px;
}
.auth-brand__mark {
	display: inline-grid;
	place-items: center;
	width: 26px;
	height: 26px;
	border-radius: 7px;
	background: #3b82f6;
	color: #0b1a33;
	font-size: 14px;
}

/* Headline */
.auth-headline {
	font-family: "Fraunces", serif;
	font-weight: 500;
	font-size: 23px;
	line-height: 1.4;
	letter-spacing: -0.005em;
	color: rgba(238, 243, 251, 0.9);
	max-width: 340px;
	margin: 0 0 36px;
}

/* Form card */
.auth-card {
	width: 100%;
	background: #ffffff;
	border: 1px solid #dbe6fb;
	border-radius: 20px;
	padding: 40px 36px;
	box-shadow: 0 30px 60px -28px rgba(15, 40, 90, 0.55);
	text-align: left;
	color: #14213d;
}

/* Compact feature footer */
.auth-features {
	list-style: none;
	display: flex;
	flex-wrap: wrap;
	justify-content: center;
	gap: 8px;
	padding: 0;
	margin: 28px 0 0;
}
.auth-features li {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	font-size: 12px;
	font-weight: 600;
	color: rgba(238, 243, 251, 0.75);
}
.auth-features li:not(:last-child)::after {
	content: "";
	width: 3px;
	height: 3px;
	border-radius: 50%;
	background: rgba(238, 243, 251, 0.35);
	margin-left: 8px;
}
.auth-features li svg {
	width: 13px;
	height: 13px;
	color: #60a5fa;
}

/* ============ RESPONSIVE ============ */
@media (max-width: 560px) {
	.auth-scroll { padding: 40px 18px; }
	.auth-headline { font-size: 20px; margin-bottom: 28px; }
	.auth-card { padding: 30px 22px; border-radius: 16px; }
	.auth-features { margin-top: 22px; }
	.auth-features li:not(:last-child)::after { display: none; }
}
</style>