@extends('core::admin.master')

@section('title', __('Departments'))

@section('content')

<item-list
    url-base="/api/departments"
    locale="{{ config('typicms.content_locale') }}"
    fields="id,status,title"
    table="departments"
    title="departments"
    appends="thumb"
    :exportable="true"
    :searchable="['title']"
    :sorting="['title_translated']">

    <template slot="add-button" v-if="$can('create departments')">
        @include('core::admin._button-create', ['module' => 'departments'])
    </template>

    <template slot="columns" slot-scope="{ sortArray }">
        <item-list-column-header name="checkbox" v-if="$can('update departments')||$can('delete departments')"></item-list-column-header>
        <item-list-column-header name="edit" v-if="$can('update departments')"></item-list-column-header>
        <item-list-column-header name="status_translated" sortable :sort-array="sortArray" :label="$t('Status')"></item-list-column-header>

        <item-list-column-header name="title_translated" sortable :sort-array="sortArray" :label="$t('Title')"></item-list-column-header>
    </template>

    <template slot="table-row" slot-scope="{ model, checkedModels, loading }">
        <td class="checkbox" v-if="$can('update departments')||$can('delete departments')"><item-list-checkbox :model="model" :checked-models-prop="checkedModels" :loading="loading"></item-list-checkbox></td>
        <td v-if="$can('update departments')">@include('core::admin._button-edit', ['module' => 'departments'])</td>
        <td><item-list-status-button :model="model"></item-list-status-button></td>
       
        <td>@{{ model.title_translated }}</td>
    </template>

</item-list>

@endsection
