#include <stdio.h>
#include <stdlib.h>
#include <stdbool.h>
typedef void sort_type(int *,int);

void swap(int *pa,int *pb);

void  heap_insert(int num,int heap[]);
void heap_up(int heap[]);
int heap_delete(int heap[]);
void heap_down(int heap[]);

//排序函数
sort_type quick_sort,heap_sort,bubble_sort,select_sort;

main(void){
	int arr[10]={78,56,2,74,44,3,5,32,455,33};
	int i,count=10;

	//堆排序
	//heap_sort(arr,count);

	//冒泡排序
	//bubble_sort(arr,count);

	//快速排序
	//quick_sort(arr,count);
	
	//选择排序
	//select_sort(arr,count);
	printf("排序之后的数组：\n");
	for (i = 0; i < count; ++i)
	{
		printf("%d ",arr[i] );
	}
}

/**
 * 堆排序 时间复杂度 n(log^n)
 * @param arr   [description]
 * @param count [description]
 */
void heap_sort(int arr[],int count){
	int *heap;
	int i;

	heap=(int *)calloc(count+1,sizeof(int));
	for (i = 0; i < count; ++i)
	{
		heap_insert(arr[i],heap);
	}

	//取出堆
	for (i = 0; i < count; ++i)
	{
		arr[i]=heap_delete(heap);
	}

	free(heap);
}

/**
 * 堆的插入
 * @param num  [description]
 * @param heap [description]
 */
void heap_insert(int num,int heap[]){
	int i;
	heap[0]+=1;

	heap[heap[0]]=num;
	heap_up(heap);
}

/**
 * 插入数组恢复堆性质
 * @param heap [description]
 */
void heap_up(int heap[]){
	int child,parent;
	int tmp;

	child=heap[0];
	parent=child/2;

	while(heap[child]<heap[parent]&&parent>0){
		swap(heap+child,heap+parent);

		child=parent;
		parent=child/2;
	}
}

/**
 * 删除堆顶的数据
 * @param sort_arr [description]
 * @param heap     [description]
 */
int heap_delete(int heap[]){
	int min;

	if(heap[0]<1){
		printf("error:no heap\n");
		exit(1);
	}
	
	min=heap[1];
	swap(heap+1,heap+heap[0]);
	heap[0]-=1;

	heap_down(heap);

	return min;
}

/**
 * 删除堆恢复堆性质
 * @param heap [description]
 */
void heap_down(int heap[]){
	int parent=1;
	int child1,child2;
	int sign;
	int min;

	do{
		sign=0;
		child1=parent*2;
		child2=child1+1;

		if(child1>heap[0]){
			break;
		}else if(child2>heap[0]){
			min=child1;
		}else{
			min=heap[child1]<heap[child2]?child1:child2;
		}

		if(heap[parent]>heap[min]){
			swap(heap+parent,heap+min);
			parent=min;
			sign=1;
		}
	}while(sign==1);
}

/**
 * 选择排序 时间复杂度n^3
 * @param arr   [description]
 * @param count [description]
 */
void select_sort(int arr[],int count){
	int i,j,min;

	for (i = 0; i < count-1; ++i)
	{
		min=i;
		for (j = i+1; j < count; ++j)
		{
			if(arr[j]<arr[min]){
				min=j;
			}
		}

		swap(arr+i,arr+min);
	}
}

/**
 * 快速排序 不需要额外变量 时间复杂度 n(log^n)
 * @param arr   [description]
 * @param count [description]
 */
void quick_sort(int arr[],int count){
	int i;
	int j=1;

	swap(arr+0,arr+count/2);

	for (i = 1; i < count; ++i)
	{
		if(arr[i]<arr[0]){
			swap(arr+j,arr+i);
			j++;
		}
	}

	swap(arr+0,arr+j-1);

	if(count<=2)
		return;
	else{
		quick_sort(arr,j);
		quick_sort(arr+j,count-j);
	}
}

/**
 * 冒泡排序  插入排序很相似 时间复杂度n^2
 * @param a     [description]
 * @param count [description]
 */
void bubble_sort(int arr[],int count){
	int i,j,tmp,sign;
	for (j = count-1; j >0; j--)
	{
		sign=0;

		for (i = 0; i < j; i++)
		{
			if(arr[i]>arr[i+1]){
				sign=1;

				swap(arr+i,arr+i+1);
			}
		}

		if(sign==0){
			break;
		}
	}
	
}

/**
 * 交换
 * @param pa [description]
 * @param pb [description]
 */
void swap(int *pa,int *pb){
	int tmp;
	tmp=*pb;
	*pb=*pa;
	*pa=tmp;
}