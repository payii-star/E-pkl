<template>
	<!--begin::Authentication Layout -->
	<div
		class="auth-page"
		:style="setting?.bg_auth ? `--auth-bg: url('${setting.bg_auth}')` : ''"
	>
		<div class="auth-page__overlay"></div>

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

			<!--begin::Compact feature footer-->
			<ul class="auth-features">
				<li>
					<svg viewBox="0 0 24 24" fill="none">
						<path d="M4 8V6a2 2 0 0 1 2-2h2M18 4h2a2 2 0 0 1 2 2v2M20 16v2a2 2 0 0 1-2 2h-2M6 20H4a2 2 0 0 1-2-2v-2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
						<circle cx="12" cy="12" r="3.2" stroke="currentColor" stroke-width="1.6"/>
					</svg>
					Face ID
				</li>
				<li>
					<svg viewBox="0 0 24 24" fill="none">
						<path d="M5 4h11l3 3v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
						<path d="M8 10h8M8 14h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
					</svg>
					Jurnal harian
				</li>
				<li>
					<svg viewBox="0 0 24 24" fill="none">
						<path d="m5 13 4 4 10-10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
					Approval pembimbing
				</li>
			</ul>
			<!--end::Compact feature footer-->
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
	min-height: 100vh;
	background-color: #152238;
	background-image: var(--auth-bg);
	background-size: cover;
	background-position: center;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 64px 24px;
	font-family: "Manrope", sans-serif;
	overflow: hidden;
}
.auth-page__overlay {
	position: absolute;
	inset: 0;
	background:
		radial-gradient(circle at 18% 12%, rgba(217, 142, 63, 0.16), transparent 42%),
		radial-gradient(circle at 82% 88%, rgba(62, 124, 89, 0.16), transparent 46%),
		linear-gradient(180deg, rgba(21, 34, 56, 0.93), rgba(21, 34, 56, 0.93));
}

.auth-center {
	position: relative;
	z-index: 1;
	width: 100%;
	max-width: 420px;
	display: flex;
	flex-direction: column;
	align-items: center;
	text-align: center;
	color: #f6f3ec;
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
	background: #d98e3f;
	color: #152238;
	font-size: 14px;
}

/* Headline */
.auth-headline {
	font-family: "Fraunces", serif;
	font-weight: 500;
	font-size: 23px;
	line-height: 1.4;
	letter-spacing: -0.005em;
	color: rgba(246, 243, 236, 0.88);
	max-width: 340px;
	margin: 0 0 36px;
}

/* Form card */
.auth-card {
	width: 100%;
	background: #ffffff;
	border: 1px solid #ece5d6;
	border-radius: 20px;
	padding: 40px 36px;
	box-shadow: 0 30px 60px -28px rgba(0, 0, 0, 0.5);
	text-align: left;
	color: #1b2a4a;
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
	color: rgba(246, 243, 236, 0.7);
}
.auth-features li:not(:last-child)::after {
	content: "";
	width: 3px;
	height: 3px;
	border-radius: 50%;
	background: rgba(246, 243, 236, 0.35);
	margin-left: 8px;
}
.auth-features li svg {
	width: 13px;
	height: 13px;
	color: #d98e3f;
}

/* ============ RESPONSIVE ============ */
@media (max-width: 560px) {
	.auth-page { padding: 40px 18px; }
	.auth-headline { font-size: 20px; margin-bottom: 28px; }
	.auth-card { padding: 30px 22px; border-radius: 16px; }
	.auth-features { margin-top: 22px; }
	.auth-features li:not(:last-child)::after { display: none; }
}
</style>