<script setup lang="ts">
import { h, ref, watch } from "vue";
import { useDelete } from "@/libs/hooks";
import Form from "./Form.vue";
import { createColumnHelper } from "@tanstack/vue-table";
import type { Testimonial } from "@/types";

const column = createColumnHelper<Testimonial>();

const paginateRef = ref<any>(null);

const selected = ref<string>("");

const openForm = ref(false);

const { delete: deleteTestimonial } = useDelete({
    onSuccess: () => {
        paginateRef.value?.refetch();
    },
});

const columns = [
    column.accessor("no", {
        header: "#",
    }),

    column.accessor("name", {
        header: "Nama",
    }),

    column.accessor("position", {
        header: "Jabatan / Instansi",
    }),

    column.accessor("message", {
        header: "Testimoni",
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
                                deleteTestimonial(
                                    `/master/testimonials/${cell.getValue()}`
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

    <!-- LIST TESTIMONIALS -->
    <div class="card">
        <div class="card-header align-items-center">
            <h2 class="mb-0">
                List Testimonials
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
                id="table-testimonials"
                url="/master/testimonials"
                :columns="columns"
            >
            </paginate>
        </div>
    </div>
</template>