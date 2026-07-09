<template>
    <AdminLayout v-if="true" pluginName="Branch418 Boilerplate" pluginDescription="branch418 plugin boilerplate">
        <Column width="4/6" type="column">
            <Section
                title="Example section"
                step="1"
                tooltip="Example tooltip"
            >
                <Card title="Hello, world">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. In ornare vel turpis id malesuada. Nulla eu tortor nec nisi condimentum volutpat. Mauris pretium viverra massa vitae posuere. Donec tincidunt ipsum molestie nunc aliquam rhoncus. In hac habitasse platea dictumst. Aliquam dignissim quis dui in egestas. Aenean efficitur velit orci, id venenatis diam volutpat at. Mauris consectetur mauris id libero ornare imperdiet sed eu massa. Donec porttitor sollicitudin massa, in ultricies odio egestas in. Curabitur rhoncus lectus et velit congue ultricies. Proin non magna nunc. Fusce lacus leo, suscipit ac ante a, sodales elementum est.
                </Card>
            </Section>
        </Column>

        <Column width="2/6" type="sidebar">
            <Section title="Example">
                <Card variant="highlighted">
                    Example sidebar card
                </Card>
            </Section>
        </Column>
    </AdminLayout>

    <!-- Subpages workspace -->
    <transition :name="transitionName" mode="out-in" v-if="true" :key="1">
        <component
            :is="components[activeComponent]"
            @back="goBack()"
        />
    </transition>
</template>

<script setup>
import {ref, inject} from 'vue';
import {
    AdminLayout, Card, Column, Section,
} from '@branch418/shared/components';

const wpData = inject('wpData');
const components = {
    Card
}

const activeComponent = ref(false)

const transitionName = ref('b418-slide');

function goBack() {
    transitionName.value = 'b418-slide-back';

    setTimeout(() => {
        transitionName.value = 'b418-slide';
    }, 300);
}
</script>

<style scoped lang="scss">
@use '@branch418/shared/styles/variables' as *;
@use '@branch418/shared/styles/global' as *;
@use '@branch418/shared/styles/transitions';
</style>