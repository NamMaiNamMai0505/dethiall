@extends('layouts.admin')

@section('title', 'Ma trận phân quyền')
@section('page-title', 'Ma trận phân quyền')

@section('content')
<div class="w-full h-full min-h-screen p-6 bg-gray-50">
    <div class="bg-white rounded-lg shadow overflow-auto w-full h-full">
        <table class="min-w-full border-collapse">
            <thead>
                <tr class="bg-gray-200">
                    <th class="p-3 border text-left">Module / Action</th>
                    @foreach($actions as $action)
                        <th class="p-3 border text-center capitalize">{{ $action }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($resources as $resource)
                <tr class="hover:bg-gray-100">
                    <td class="p-2 border font-medium">{{ ucfirst(str_replace('-', ' ', $resource)) }}</td>
                    @foreach($actions as $action)
                        @php $perm = "{$resource}.{$action}"; @endphp
                        <td class="p-2 border text-center">
                            @if(in_array($perm, $existing))
                                <i class="bi bi-check-circle-fill text-green-500 text-lg"></i>
                            @else
                                <i class="bi bi-x-circle-fill text-red-500 text-lg"></i>
                            @endif
                        </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

