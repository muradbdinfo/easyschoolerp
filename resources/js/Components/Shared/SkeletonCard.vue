<template>
    <Card>
        <template #content>
            <Skeleton height="2rem" class="mb-2" />
            <Skeleton height="4rem" />
        </template>
    </Card>
</template>

<script setup>
import Card from 'primevue/card';
import Skeleton from 'primevue/skeleton';
</script>