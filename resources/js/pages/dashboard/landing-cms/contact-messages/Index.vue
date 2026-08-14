<script setup lang="ts">
import { h, ref } from "vue";
import { useDelete } from "@/libs/hooks";
import Detail from "./Detail.vue";
import { createColumnHelper } from "@tanstack/vue-table";
import type { ContactMessage } from "@/types";

const column = createColumnHelper<ContactMessage>();
const paginateRef = ref<any>(null);
const selected = ref<string>("");
const openDetail = ref<boolean>(false);

const { delete: deleteMessage } = useDelete({
    onSuccess: () => paginateRef.value.refetch(),
});

const columns = [
    column.accessor("no", {
        header: "#",
    }),
    column.accessor("name", {
        header: "Nama",
    }),
    column.accessor("email", {
        header: "Email",
    }),
    column.accessor("subject", {
        header: "Subjek",
    }),
    column.accessor("created_at", {
        header: "Tanggal Masuk",
    }),
    column.accessor("uuid", {
        header: "Aksi",
        cell: (cell) =>
            h("div", { class: "d-flex gap-2" }, [
                h(
                    "button",
                    {
                        class: "btn btn-sm btn-icon btn-info",
                        onClick: () => {
                            selected.value = cell.getValue();
                            openDetail.value = true;
                        },
                    },
                    h("i", { class: "la la-eye fs-2" })
                ),
                h(
                    "button",
                    {
                        class: "btn btn-sm btn-icon btn-danger",
                        onClick: () =>
                            // TODO: cocokkan endpoint ini dengan API Destria
                            deleteMessage(
                                `/master/contact-messages/${cell.getValue()}`
                            ),
                    },
                    h("i", { class: "la la-trash fs-2" })
                ),
            ]),
    }),
];
</script>

<template>
    <Detail
        :selected="selected"
        @close="openDetail = false"
        v-if="openDetail"
    />

    <div class="card">
        <div class="card-header align-items-center">
            <h2 class="mb-0">List Pesan Masuk</h2>
        </div>
        <div class="card-body">
            <paginate
                ref="paginateRef"
                id="table-contact-messages"
                url="/master/contact-messages"
                :columns="columns"
            ></paginate>
        </div>
    </div>
</template>