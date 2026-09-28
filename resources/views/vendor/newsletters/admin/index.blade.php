@extends('core::admin.master')

@section('title', __('Newsletters'))

@section('content')

<item-list
    url-base="/api/newsletters"
    locale="{{ config('typicms.content_locale') }}"
    fields="id,name,email,type"
    table="newsletters"
    title="newsletters"
    include="image"
    appends="thumb"
    :exportable="true"
    :searchable="['title']"
    :sorting="[]">

    <template slot="add-button" v-if="$can('create newsletters')">
        @include('core::admin._button-create', ['module' => 'newsletters'])
    </template>

    <template slot="columns" slot-scope="{ sortArray }">
        <item-list-column-header name="checkbox" v-if="$can('update newsletters')||$can('delete newsletters')"></item-list-column-header>
        <item-list-column-header name="edit" v-if="$can('update newsletters')"></item-list-column-header>
        <item-list-column-header name="name" :label="$t('Name')"></item-list-column-header>
        <item-list-column-header name="email" :label="$t('Email')"></item-list-column-header>
        <item-list-column-header name="type" :label="$t('Type')"></item-list-column-header>
    </template>

    <template slot="table-row" slot-scope="{ model, checkedModels, loading }">
        <td class="checkbox" v-if="$can('update newsletters')||$can('delete newsletters')"><item-list-checkbox :model="model" :checked-models-prop="checkedModels" :loading="loading"></item-list-checkbox></td>
        <td v-if="$can('update newsletters')">@include('core::admin._button-edit', ['module' => 'newsletters'])</td>
        <td>@{{ model.name }}</td>
        <td>@{{ model.email }}</td>
        <td>@{{ model.type }}</td>
    </template>

</item-list>

@endsection
