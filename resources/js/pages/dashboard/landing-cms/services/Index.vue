<script setup lang="ts">
import { h, ref, watch } from "vue";
import { useDelete } from "@/libs/hooks";
import Form from "./Form.vue";
import { createColumnHelper } from "@tanstack/vue-table";
import type { Service } from "@/types";

const column = createColumnHelper<Service>();

const paginateRef = ref<any>(null);

const selected = ref<string>("");

const openForm = ref(false);

const { delete: deleteService } = useDelete({
    onSuccess: () => {
        paginateRef.value?.refetch();
    },
});

const columns = [
    column.accessor("no", {
        header: "#",
    }),

    column.accessor("title", {
        header: "Judul Layanan",
    }),

    column.accessor("description", {
        header: "Deskripsi",
    }),

    column.accessor("icon", {
        header: "Icon",

        cell: (cell) =>
            h(
                "i",
                cell.getValue()
                    ? { class: `fa-solid fa-${cell.getValue()} fs-2` }
                    : { class: "text-muted" },
                cell.getValue() ? "" : "-"
            ),
    }),

    column.accessor("order", {
        header: "Urutan",
    }),

    column.accessor("uuid", {
        header: "Aksi",

        cell: (cell) =>
            h(
                "div",
                {
                    class: "d-flex gap-2",
                },
                [
                    // EDIT
                    h(
                        "button",
                        {
                            type: "button",

                            class: "btn btn-sm btn-icon btn-info",

                            onClick: () => {
                                selected.value =
                                    cell.getValue();

                                openForm.value = true;
                            },
                        },
                        [
                            h("i", {
                                class: "la la-pencil fs-2",
                            }),
                        ]
                    ),

                    // DELETE
                    h(
                        "button",
                        {
                            type: "button",

                            class: "btn btn-sm btn-icon btn-danger",

                            onClick: () => {
                                deleteService(
                                    `/master/services/${cell.getValue()}`
                                );
                            },
                        },
                        [
                            h("i", {
                                class: "la la-trash fs-2",
                            }),
                        ]
                    ),
                ]
            ),
    }),
];

function onAdd() {
    selected.value = "";
    openForm.value = true;
}

function onClose() {
    openForm.value = false;
}

function refresh() {
    paginateRef.value?.refetch();
}

watch(openForm, (value) => {
    if (!value) {
        selected.value = "";
    }

    window.scrollTo(0, 0);
});
</script>

<template>
    <!-- FORM TAMBAH / EDIT -->
    <Form
        v-if="openForm"
        :selected="selected"
        @close="onClose"
        @refresh="refresh"
    />

    <!-- LIST SERVICES -->
    <div class="card">
        <div class="card-header align-items-center">
            <h2 class="mb-0">
                List Services
            </h2>

            <button
                v-if="!openForm"
                type="button"
                class="btn btn-sm btn-primary ms-auto"
                @click="onAdd"
            >
                Tambah

                <i class="la la-plus"></i>
            </button>
        </div>

        <div class="card-body">
            <paginate
                ref="paginateRef"
                id="table-services"
                url="/master/services"
                :columns="columns"
            >
            </paginate>
        </div>
    </div>
</template>